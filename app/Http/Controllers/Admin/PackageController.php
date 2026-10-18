<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\User;
use App\PackageType;
use App\Services\AdminAuditLogger;
use App\Support\InrMoney;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(): View
    {
        return view('admin.packages.index', ['packages' => Package::query()->withCount('purchases')->orderBy('display_order')->orderBy('id')->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.packages.form', ['package' => null]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $actor = $this->actor($request);
        $package = Package::query()->create($data);
        $audit->record($actor, 'package.created', $package, 'Created package '.$package->name.'.', null, null, $package->only(['type', 'name', 'price_paise', 'currency', 'job_posting_credits', 'candidate_access_credits', 'is_active']));

        return redirect()->route('admin.packages.index')->with('status', 'Package created.');
    }

    public function edit(Package $package): View
    {
        return view('admin.packages.form', compact('package'));
    }

    public function update(Request $request, Package $package, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $before = $package->only(['type', 'name', 'price_paise', 'currency', 'job_posting_credits', 'listing_validity_days', 'candidate_access_credits', 'entitlement_validity_days', 'is_active', 'display_order']);
        $package->update($data);
        $audit->record($this->actor($request), 'package.updated', $package, 'Updated package '.$package->name.'.', null, $before, $package->only(array_keys($before)));

        return redirect()->route('admin.packages.index')->with('status', 'Package updated. Existing purchases retain their original snapshots.');
    }

    public function setActive(Request $request, Package $package, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $before = ['is_active' => $package->is_active];
        $package->update(['is_active' => (bool) $data['is_active']]);
        $audit->record($this->actor($request), 'package.visibility_updated', $package, $package->is_active ? 'Activated package '.$package->name.'.' : 'Deactivated package '.$package->name.'.', null, $before, ['is_active' => $package->is_active]);

        return back()->with('status', 'Package visibility updated.');
    }

    public function purchases(): View
    {
        $purchases = PackagePurchase::query()->with(['buyer:id,name,email', 'payments', 'entitlement'])->latest('created_at')->paginate(30);

        return view('admin.packages.purchases', compact('purchases'));
    }

    public function showPurchase(PackagePurchase $purchase): View
    {
        $purchase->load(['buyer:id,name,email', 'package', 'payments' => fn ($query) => $query->latest('id'), 'entitlement']);

        return view('admin.packages.purchase', compact('purchase'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_column(PackageType::cases(), 'value'))],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'string', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'job_posting_credits' => ['nullable', 'integer', 'between:1,10000'],
            'listing_validity_days' => ['nullable', 'integer', 'between:1,3650'],
            'candidate_access_credits' => ['nullable', 'integer', 'between:1,10000'],
            'entitlement_validity_days' => ['nullable', 'integer', 'between:1,3650'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['nullable', 'string', 'max:160'],
            'display_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ]);

        $type = PackageType::from($data['type']);
        if (InrMoney::toPaise($data['price']) < 1) {
            throw ValidationException::withMessages(['price' => 'Price must be at least ₹0.01.']);
        }

        $features = array_values(array_filter(array_map(static fn (mixed $feature): string => is_string($feature) ? trim($feature) : '', $data['features'] ?? []), static fn (string $feature): bool => $feature !== ''));
        $jobPackage = $type === PackageType::JobPosting;
        if ($jobPackage && (empty($data['job_posting_credits']) || empty($data['listing_validity_days']))) {
            throw ValidationException::withMessages(['job_posting_credits' => 'Job packages need both posting credits and listing validity days.']);
        }
        if (! $jobPackage && (empty($data['candidate_access_credits']) || empty($data['entitlement_validity_days']))) {
            throw ValidationException::withMessages(['candidate_access_credits' => 'Candidate-access packages need access credits and an entitlement validity period.']);
        }

        return [
            'type' => $type,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_paise' => InrMoney::toPaise($data['price']),
            'currency' => 'INR',
            'job_posting_credits' => $jobPackage ? (int) $data['job_posting_credits'] : null,
            'listing_validity_days' => $jobPackage ? (int) $data['listing_validity_days'] : null,
            'candidate_access_credits' => $jobPackage ? null : (int) $data['candidate_access_credits'],
            'entitlement_validity_days' => $jobPackage ? null : (int) $data['entitlement_validity_days'],
            'features' => $features,
            'display_order' => (int) $data['display_order'],
            'is_active' => (bool) $data['is_active'],
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || $actor->isOwner()) && ! $actor->isSuspended(), 403);

        return $actor;
    }
}
