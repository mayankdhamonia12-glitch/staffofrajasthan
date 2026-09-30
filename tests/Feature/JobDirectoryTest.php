<?php

use App\Livewire\Jobs\Index;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->withoutVite());

function directoryJob(array $attributes = []): Job
{
    static $sequence = 0;
    $sequence++;

    $employer = User::factory()->employer()->create();
    $place = $attributes['place'] ?? 'Jaipur';
    $sector = $attributes['sector'] ?? 'Technology';
    $field = $attributes['field'] ?? 'IT & Software';
    $location = Location::firstOrCreate(['name' => $place, 'state' => 'Rajasthan'], ['slug' => 'place-'.$sequence]);
    $industry = Industry::firstOrCreate(['name' => $sector], ['slug' => 'sector-'.$sequence]);
    $category = JobCategory::firstOrCreate(['name' => $field], ['slug' => 'field-'.$sequence]);
    $company = $employer->companies()->create([
        'name' => $attributes['company'] ?? 'Example Co '.$sequence,
        'slug' => 'company-'.$sequence,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ]);

    return $company->jobs()->create(array_merge([
        'title' => $attributes['title'] ?? 'Developer '.$sequence,
        'slug' => 'job-'.$sequence,
        'description' => $attributes['description'] ?? 'Build reliable software.',
        'employment_type' => $attributes['type'] ?? 'full_time',
        'experience_level' => $attributes['experience'] ?? 'Mid level',
        'salary_min' => $attributes['salary_min'] ?? 300000,
        'salary_max' => $attributes['salary_max'] ?? 600000,
        'status' => $attributes['status'] ?? 'published',
        'published_at' => $attributes['published_at'] ?? now()->subDays($sequence),
        'is_filled' => $attributes['is_filled'] ?? false,
        'application_deadline' => $attributes['deadline'] ?? null,
        'job_category_id' => $category->id,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ], $attributes['job'] ?? []));
}

test('the jobs archive returns only current published opportunities', function () {
    directoryJob(['title' => 'Published developer']);
    directoryJob(['title' => 'Draft role', 'status' => 'draft']);
    directoryJob(['title' => 'Future role', 'published_at' => now()->addDay()]);
    directoryJob(['title' => 'Filled role', 'is_filled' => true]);
    directoryJob(['title' => 'Expired role', 'deadline' => today()->subDay()]);

    Livewire::test(Index::class)
        ->assertSee('Published developer')
        ->assertDontSee('Draft role')
        ->assertDontSee('Future role')
        ->assertDontSee('Filled role')
        ->assertDontSee('Expired role');
});

test('the public jobs route renders a searchable directory', function () {
    directoryJob(['title' => 'Route Rendered Opportunity']);

    $this->get(route('jobs.index', ['keyword' => 'Route']))
        ->assertOk()
        ->assertSee('Browse jobs')
        ->assertSee('Route Rendered Opportunity');
});

test('the directory searches by job title, company, skills and location', function () {
    $target = directoryJob(['title' => 'Senior PHP Engineer', 'place' => 'Udaipur', 'company' => 'Desert Digital']);
    $target->skills()->attach(Skill::create(['name' => 'Livewire']));
    directoryJob(['title' => 'Accountant', 'place' => 'Jaipur']);

    Livewire::test(Index::class)->set('keyword', 'PHP')->assertSee('Senior PHP Engineer')->assertDontSee('Accountant');
    Livewire::test(Index::class)->set('keyword', 'Desert Digital')->assertSee('Senior PHP Engineer');
    Livewire::test(Index::class)->set('keyword', 'Livewire')->assertSee('Senior PHP Engineer');
    Livewire::test(Index::class)->set('location', 'Udaipur')->assertSee('Senior PHP Engineer')->assertDontSee('Accountant');
});

test('filters work together for category, industry, type, experience, salary and date', function () {
    $job = directoryJob(['title' => 'Recent Jaipur Designer', 'place' => 'Jaipur', 'field' => 'Design', 'sector' => 'Creative', 'type' => 'contract', 'experience' => 'Senior', 'salary_min' => 700000, 'published_at' => now()]);
    directoryJob(['title' => 'Older developer', 'place' => 'Jaipur', 'field' => 'IT', 'sector' => 'Technology', 'type' => 'full_time', 'experience' => 'Junior', 'salary_min' => 200000, 'published_at' => now()->subDays(90)]);

    Livewire::test(Index::class)
        ->set('category', $job->category->slug)
        ->set('industry', $job->industry->slug)
        ->set('employmentType', 'contract')
        ->set('experienceLevel', 'Senior')
        ->set('minimumSalary', '500000')
        ->set('postedWithin', '7')
        ->assertSee('Recent Jaipur Designer')
        ->assertDontSee('Older developer');
});

test('sorting and layout selection update the directory results', function () {
    directoryJob(['title' => 'Lower salary', 'salary_min' => 200000, 'salary_max' => 300000]);
    directoryJob(['title' => 'Higher salary', 'salary_min' => 900000, 'salary_max' => 1200000]);

    Livewire::test(Index::class)
        ->set('sort', 'salary_high')
        ->set('view', 'grid')
        ->assertSee('Higher salary')
        ->assertSee('sm:grid-cols-2');
});

test('the directory paginates job results', function () {
    foreach (range(1, 13) as $index) {
        directoryJob([
            'title' => 'Pagination role '.$index,
            'published_at' => now()->subMinutes($index),
        ]);
    }

    Livewire::test(Index::class)
        ->assertSee('Pagination role 1')
        ->assertDontSee('Pagination role 13')
        ->call('nextPage')
        ->assertSee('Pagination role 13')
        ->assertDontSee('Pagination role 1</h3>');
});

test('homepage features current jobs and real company/category data', function () {
    $job = directoryJob(['title' => 'Rajasthan Operations Lead', 'job' => ['is_featured' => true]]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Rajasthan Operations Lead')
        ->assertSee('Example Co')
        ->assertSee($job->category->name)
        ->assertSee($job->company->industry->name)
        ->assertSee($job->company->location->name)
        ->assertSee(route('jobs.index', ['category' => $job->category->slug]))
        ->assertSee(route('companies.index'))
        ->assertDontSee('Role matched to your skills');

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Example Co')
        ->assertSee(route('companies.show', $job->company));

    $this->get(route('companies.show', $job->company))
        ->assertOk()
        ->assertSee($job->company->name)
        ->assertSee('Rajasthan Operations Lead');
});

test('homepage category cards show published counts and preserve the jobs filter', function () {
    $publishedOne = directoryJob(['title' => 'Category opening one']);
    directoryJob(['title' => 'Category opening two', 'job' => ['job_category_id' => $publishedOne->category->id]]);
    directoryJob(['title' => 'Category draft', 'status' => 'draft', 'job' => ['job_category_id' => $publishedOne->category->id]]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($publishedOne->category->name)
        ->assertSee('2 open roles')
        ->assertSee(route('jobs.index', ['category' => $publishedOne->category->slug]));

    $this->get(route('jobs.index', ['category' => $publishedOne->category->slug]))
        ->assertOk()
        ->assertSee('Category opening one')
        ->assertSee('Category opening two')
        ->assertDontSee('Category draft');
});

test('homepage features only public featured jobs and orders latest jobs by publication', function () {
    $latest = directoryJob(['title' => 'Most recent public listing', 'published_at' => now()->subMinutes(1), 'job' => ['is_featured' => true]]);
    $older = directoryJob(['title' => 'Older public listing', 'published_at' => now()->subDay()]);
    $draft = directoryJob(['title' => 'Draft featured listing', 'status' => 'draft', 'job' => ['is_featured' => true]]);
    directoryJob(['title' => 'Expired featured listing', 'deadline' => today()->subDay(), 'job' => ['is_featured' => true]]);

    $response = $this->get(route('home'))
        ->assertOk()
        ->assertSee('Most recent public listing')
        ->assertSee('Older public listing')
        ->assertDontSee('Draft featured listing')
        ->assertDontSee('Expired featured listing')
        ->assertSee(route('jobs.show', $latest));

    expect(strpos($response->getContent(), 'Most recent public listing'))
        ->toBeLessThan(strpos($response->getContent(), 'Older public listing'));

    $this->get(route('jobs.show', $older))
        ->assertOk()
        ->assertSee('Job description')
        ->assertSee(route('companies.show', $older->company));

    $this->get(route('jobs.show', $draft))->assertNotFound();
});

test('job detail route rejects jobs that are not publicly available', function () {
    $draft = directoryJob(['title' => 'Private detail listing', 'status' => 'draft']);

    $this->get(route('jobs.show', $draft))->assertNotFound();
});

test('homepage search submits keyword and location to the searchable jobs directory', function () {
    directoryJob(['title' => 'Udaipur Laravel Engineer', 'place' => 'Udaipur']);
    directoryJob(['title' => 'Jaipur Accountant', 'place' => 'Jaipur']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('action="'.route('jobs.index').'"', false)
        ->assertSee('method="GET"', false)
        ->assertSee('name="keyword"', false)
        ->assertSee('name="location"', false);

    $this->get(route('jobs.index', ['keyword' => 'Laravel', 'location' => 'Udaipur']))
        ->assertOk()
        ->assertSee('Udaipur Laravel Engineer')
        ->assertDontSee('Jaipur Accountant');
});
