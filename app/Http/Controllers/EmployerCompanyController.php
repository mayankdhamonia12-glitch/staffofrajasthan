<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Industry;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployerCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = $request->user()->companies()
            ->with(['industry', 'location'])
            ->withCount(['jobs as published_jobs_count' => fn ($jobs) => $jobs->publiclyAvailable()])
            ->latest()
            ->get();

        return view('employer.companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('employer.companies.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $company = $request->user()->companies()->create($this->companyAttributes($data));
        $this->storeBranding($request, $company);

        return redirect()->route('employer.companies.edit', $company)->with('status', 'Company profile created.');
    }

    public function edit(Request $request, Company $company): View
    {
        $company = $this->ownedCompany($request, $company);

        return view('employer.companies.form', [...$this->formData(), 'company' => $company]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $company = $this->ownedCompany($request, $company);
        $data = $this->validated($request, $company);
        $company->update($this->companyAttributes($data, $company));
        $this->storeBranding($request, $company);

        return back()->with('status', 'Company profile updated.');
    }

    /** @return array{industries: Collection<int, Industry>, locations: Collection<int, Location>} */
    private function formData(): array
    {
        return [
            'industries' => Industry::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'state']),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id,is_active,1'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id,is_active,1'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:500'],
            'company_size' => ['nullable', Rule::in(['1-10', '11-50', '51-200', '201-500', '501-1000', '1001-5000', '5001+'])],
            'founded_year' => ['nullable', 'integer', 'between:1800,'.now()->year],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function companyAttributes(array $data, ?Company $company = null): array
    {
        $attributes = $data;
        unset($attributes['logo'], $attributes['cover']);
        $attributes['slug'] = $this->uniqueSlug((string) $data['name'], $company);

        return $attributes;
    }

    private function uniqueSlug(string $name, ?Company $company = null): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $suffix = 2;

        $query = Company::query()->where('slug', $slug);
        if ($company !== null) {
            $query->where($company->getKeyName(), '!=', $company->getKey());
        }

        while ($query->exists()) {
            $slug = $base.'-'.$suffix++;
            $query = Company::query()->where('slug', $slug);
            if ($company !== null) {
                $query->where($company->getKeyName(), '!=', $company->getKey());
            }
        }

        return $slug;
    }

    private function ownedCompany(Request $request, Company $company): Company
    {
        return $request->user()->companies()->whereKey($company->getKey())->firstOrFail();
    }

    private function storeBranding(Request $request, Company $company): void
    {
        foreach (['logo' => 'logo_path', 'cover' => 'cover_path'] as $input => $column) {
            $file = $request->file($input);
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $oldPath = $company->{$column};
            $path = $file->storeAs(
                'companies/'.$company->getKey().'/'.$input,
                Str::uuid()->toString().'.'.$file->extension(),
                'public',
            );
            $company->update([$column => $path]);
            if (is_string($oldPath) && $oldPath !== '') {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }
}
