<?php

test('the public homepage presents the job portal and uses live domain data', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Find a job that moves you')
        ->assertSee('forward.')
        ->assertSee('Explore popular categories')
        ->assertSee('Featured jobs')
        ->assertSee('The latest opportunities')
        ->assertSee('Employers to know')
        ->assertSee('The blog is coming soon')
        ->assertSee('Create candidate account')
        ->assertSee('Create employer account')
        ->assertSee('name="keyword"', false)
        ->assertSee('name="location"', false)
        ->assertSee('action="'.route('jobs.index').'"', false)
        ->assertSee(route('candidates.index'))
        ->assertSee(route('companies.index'))
        ->assertSee(route('blog.index'))
        ->assertDontSee('Role matched to your skills');
});

test('public information pages and authentication pages share the public site shell', function () {
    foreach ([
        route('candidates.index'),
        route('companies.index'),
        route('blog.index'),
        route('about'),
        route('contact'),
        route('login'),
        route('register'),
    ] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Staff of')
            ->assertSee('Rajasthan')
            ->assertSee('For employers')
            ->assertSee('© '.now()->year.' Staff of Rajasthan');
    }
});
