<?php

use App\Models\JobCategory;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function publicCandidate(array $profile = [], array $user = []): array
{
    $candidate = User::factory()->candidate()->create($user);
    $profile = $candidate->candidateProfile()->create(array_merge([
        'slug' => 'candidate-'.$candidate->id,
        'headline' => 'Registered nurse',
        'about' => 'Experienced healthcare professional.',
        'city' => 'Jaipur',
        'state' => 'Rajasthan',
        'is_public' => true,
    ], $profile));

    return [$candidate, $profile];
}

function pngImage(int $width, int $height): string
{
    $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
    $row = "\0".str_repeat("\xff", $width * 3);

    return "\x89PNG\r\n\x1a\n".$chunk('IHDR', $header).$chunk('IDAT', gzcompress(str_repeat($row, $height))).$chunk('IEND', '');
}

test('directory lists only public candidate profiles and filters actual profile data', function () {
    [$jaipurUser, $jaipur] = publicCandidate(['slug' => 'jaipur-nurse']);
    [$privateUser, $private] = publicCandidate(['slug' => 'private-nurse', 'is_public' => false]);
    [$udaipurUser] = publicCandidate(['slug' => 'udaipur-accountant', 'city' => 'Udaipur', 'headline' => 'Accountant']);
    $skill = Skill::create(['name' => 'Patient care']);
    $jaipur->skills()->attach($skill, ['proficiency' => 'expert']);
    $category = JobCategory::create(['name' => 'Healthcare', 'slug' => 'healthcare', 'is_active' => true]);
    $jaipur->categories()->attach($category);

    $this->get(route('candidates.index'))->assertOk()->assertSee($jaipurUser->name)->assertDontSee($privateUser->name);
    $this->get(route('candidates.index', ['keyword' => 'patient']))->assertOk()->assertSee($jaipurUser->name)->assertDontSee($udaipurUser->name);
    $this->get(route('candidates.index', ['location' => 'Udaipur']))->assertOk()->assertSee($udaipurUser->name)->assertDontSee($jaipurUser->name);
    $this->get(route('candidates.index', ['category' => 'healthcare']))->assertOk()->assertSee($jaipurUser->name)->assertDontSee($udaipurUser->name);
    $this->get(route('candidates.show', $private->slug))->assertNotFound();
});

test('a candidate can opt into the public directory and its public photo endpoint respects visibility', function () {
    Storage::fake('local');
    $candidate = User::factory()->candidate()->create(['name' => 'Arjun Sharma']);
    $this->actingAs($candidate)->patch(route('candidate.profile.update'), [
        'name' => 'Arjun Sharma',
        'headline' => 'Product designer',
        'about' => 'Designing useful products.',
        'city' => 'Jaipur',
        'is_public' => 1,
    ])->assertRedirect();

    $profile = $candidate->candidateProfile()->firstOrFail();
    expect($profile->slug)->not->toBeNull()->and($profile->is_public)->toBeTrue();
    $this->get(route('candidates.show', $profile->slug))->assertOk()->assertSee('Arjun Sharma');

    $path = 'candidate-private/photos/'.$candidate->id.'/portrait.png';
    Storage::disk('local')->put($path, pngImage(64, 64));
    $profile->update(['profile_photo_path' => $path]);
    $this->get(route('candidates.photo', $profile->slug))->assertOk();
    $profile->update(['is_public' => false]);
    $this->get(route('candidates.photo', $profile->slug))->assertNotFound();
});

test('public profile shows professional data but omits contact, private resume and exact address', function () {
    [$user, $profile] = publicCandidate([
        'phone' => '9999999999',
        'address' => 'Private House, Jaipur',
        'resume_path' => 'candidate-private/resumes/1/cv.pdf',
        'expected_salary' => 900000,
        'show_expected_salary' => false,
    ], ['email' => 'private-candidate@example.test']);
    $profile->languages()->create(['language' => 'Hindi', 'proficiency' => 'native']);
    $profile->portfolios()->create(['title' => 'Community project', 'url' => 'https://example.test/project']);

    $this->get(route('candidates.show', $profile->slug))->assertOk()
        ->assertSee('Experienced healthcare professional.')
        ->assertSee('Community project')
        ->assertDontSee($user->email)
        ->assertDontSee('9999999999')
        ->assertDontSee('Private House')
        ->assertDontSee('900,000')
        ->assertDontSee('cv.pdf');
});

test('candidate profile sections are editable only by the owning candidate', function () {
    [$candidate, $profile] = publicCandidate(['is_public' => false]);
    $otherCandidate = User::factory()->candidate()->create();
    $portfolio = $profile->portfolios()->create(['title' => 'Demo', 'url' => 'https://example.test']);

    $this->actingAs($candidate)->get(route('candidate.profile.edit'))->assertOk()->assertSee('Professional details');
    $this->actingAs($otherCandidate)->put(route('candidate.portfolios.update', $portfolio), ['title' => 'Stolen edit'])->assertNotFound();
    $this->actingAs($otherCandidate)->delete(route('candidate.portfolios.destroy', $portfolio))->assertNotFound();

    $this->actingAs($candidate)->post(route('candidate.languages.store'), ['language' => 'hInDi', 'proficiency' => 'fluent'])->assertRedirect();
    expect($profile->fresh()->languages()->firstOrFail()->language)->toBe('Hindi');
});

test('resume and photo are stored privately and only the candidate may retrieve them', function () {
    Storage::fake('local');
    [$candidate, $profile] = publicCandidate(['is_public' => false]);
    $otherCandidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->post(route('candidate.resume.store'), [
        'resume' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\nCandidate CV\n%%EOF"),
        'resume_title' => 'My CV',
    ])->assertRedirect();
    $storedResume = $profile->fresh()->resume_path;
    expect($storedResume)->not->toBeNull();
    Storage::disk('local')->assertExists($storedResume);
    $this->actingAs($candidate)->get(route('candidate.resume.download'))->assertOk();
    $this->actingAs($otherCandidate)->get(route('candidate.resume.download'))->assertNotFound();

    $this->actingAs($candidate)->post(route('candidate.profile.photo.store'), ['photo' => UploadedFile::fake()->createWithContent('portrait.png', pngImage(120, 120))])->assertRedirect();
    $this->assertNotNull($profile->fresh()->profile_photo_path);
    $this->actingAs($otherCandidate)->get(route('candidate.profile.photo'))->assertNotFound();
});

test('companies can shortlist a candidate profile once for the future employer workflow', function () {
    [$candidate, $profile] = publicCandidate();
    $employer = User::factory()->employer()->create();
    $company = $employer->companies()->create(['name' => 'Test Company', 'slug' => 'test-company']);

    $company->shortlistedCandidates()->attach($profile);
    expect($company->shortlistedCandidates()->count())->toBe(1)
        ->and($profile->shortlists()->firstOrFail()->candidate_profile_id)->toBe($profile->id)
        ->and($candidate->candidateProfile()->firstOrFail()->is_public)->toBeTrue();
});
