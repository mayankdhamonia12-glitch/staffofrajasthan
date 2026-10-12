<?php

use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->withoutVite());

function employerCompanyFixture(array $attributes = []): Company
{
    static $sequence = 0;
    $sequence++;
    $employer = User::factory()->employer()->create();
    $industry = Industry::firstOrCreate(['name' => $attributes['industry'] ?? 'Technology'], ['slug' => 'employer-industry-'.$sequence]);
    $location = Location::firstOrCreate(['name' => $attributes['location'] ?? 'Jaipur', 'state' => 'Rajasthan'], ['slug' => 'employer-location-'.$sequence]);

    return $employer->companies()->create([
        'name' => $attributes['name'] ?? 'Rajasthan Systems '.$sequence,
        'slug' => $attributes['slug'] ?? 'rajasthan-systems-'.$sequence,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
        'company_size' => $attributes['company_size'] ?? '11-50',
        'is_visible' => $attributes['is_visible'] ?? true,
        'description' => $attributes['description'] ?? 'A public company profile.',
        'email' => $attributes['email'] ?? 'hello'.$sequence.'@example.test',
    ]);
}

function employerPublicJob(Company $company, array $attributes = []): Job
{
    static $sequence = 0;
    $sequence++;

    return $company->jobs()->create(array_merge([
        'title' => 'Public role '.$sequence,
        'slug' => 'public-role-'.$company->getKey().'-'.$sequence,
        'description' => 'A real published opportunity.',
        'employment_type' => 'full_time',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'is_filled' => false,
    ], $attributes));
}

function employerPngImage(int $width, int $height): string
{
    $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
    $row = "\0".str_repeat("\xff", $width * 3);

    return "\x89PNG\r\n\x1a\n".$chunk('IHDR', $header).$chunk('IDAT', gzcompress(str_repeat($row, $height))).$chunk('IEND', '');
}

test('the public company directory searches visible companies and counts published jobs', function () {
    $visible = employerCompanyFixture(['name' => 'Desert Health Group', 'industry' => 'Healthcare']);
    employerPublicJob($visible);
    employerCompanyFixture(['name' => 'Hidden Company', 'is_visible' => false]);
    employerCompanyFixture(['name' => 'Technology Partners']);

    $this->get(route('companies.index', ['keyword' => 'Desert', 'company_size' => '11-50']))
        ->assertOk()
        ->assertSee('Desert Health Group')
        ->assertSee('1 open role')
        ->assertDontSee('Hidden Company')
        ->assertDontSee('Technology Partners');
});

test('public company profiles expose only visible company data and current jobs', function () {
    $company = employerCompanyFixture(['name' => 'Public Profile Co']);
    $publicJob = employerPublicJob($company, ['title' => 'Visible opportunity']);
    employerPublicJob($company, ['title' => 'Draft opportunity', 'status' => 'draft']);
    $company->update(['website' => 'https://public.example.test', 'address' => 'Jaipur, Rajasthan', 'founded_year' => 2014]);

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertSee('Public Profile Co')
        ->assertSee('Visible opportunity')
        ->assertSee('Jaipur, Rajasthan')
        ->assertSee('https://public.example.test')
        ->assertDontSee('Draft opportunity')
        ->assertDontSee($company->user->email);

    employerCompanyFixture(['name' => 'Private Profile', 'slug' => 'private-profile', 'is_visible' => false]);
    $this->get('/companies/private-profile')->assertNotFound();
    $this->get('/companies/does-not-exist')->assertNotFound();
    expect($publicJob->exists)->toBeTrue();
});

test('an employer can create and update its own public company profile with branding', function () {
    Storage::fake('public');
    $employer = User::factory()->employer()->create();

    $response = $this->actingAs($employer)->post(route('employer.companies.store'), [
        'name' => 'Employer Owned Studio',
        'company_size' => '51-200',
        'description' => 'Our team and mission.',
        'is_visible' => '1',
        'logo' => UploadedFile::fake()->createWithContent('logo.png', employerPngImage(160, 160)),
        'cover' => UploadedFile::fake()->createWithContent('cover.png', employerPngImage(1200, 400)),
    ]);

    $company = $employer->companies()->firstOrFail();
    $response->assertRedirect(route('employer.companies.edit', $company));
    expect($company->fresh()->slug)->toBe('employer-owned-studio');
    Storage::disk('public')->assertExists($company->fresh()->logo_path);
    Storage::disk('public')->assertExists($company->fresh()->cover_path);

    $this->actingAs($employer)->patch(route('employer.companies.update', $company), [
        'name' => 'Employer Owned Studio Updated',
        'is_visible' => '0',
    ])->assertRedirect();

    expect($company->fresh()->name)->toBe('Employer Owned Studio Updated')
        ->and($company->fresh()->is_visible)->toBeFalse();
});

test('company management is restricted to the owning employer', function () {
    $company = employerCompanyFixture();
    $otherEmployer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($otherEmployer)->get(route('employer.companies.edit', $company))->assertNotFound();
    $this->actingAs($otherEmployer)->patch(route('employer.companies.update', $company), ['name' => 'Attempt'])->assertNotFound();
    $this->actingAs($candidate)->get(route('employer.companies.index'))->assertForbidden();
    $this->actingAs($candidate)->post(route('employer.companies.store'), ['name' => 'Nope'])->assertForbidden();
});

test('company branding replacement removes the previous public asset', function () {
    Storage::fake('public');
    $company = employerCompanyFixture();
    $employer = $company->user;
    $this->actingAs($employer)->post(route('employer.companies.update', $company), [
        '_method' => 'PATCH',
        'name' => $company->name,
        'logo' => UploadedFile::fake()->createWithContent('first.png', employerPngImage(160, 160)),
    ]);
    $firstPath = $company->fresh()->logo_path;
    $this->actingAs($employer)->patch(route('employer.companies.update', $company), [
        'name' => $company->name,
        'logo' => UploadedFile::fake()->createWithContent('second.png', employerPngImage(160, 160)),
    ]);
    expect($company->fresh()->logo_path)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($company->fresh()->logo_path);
});
