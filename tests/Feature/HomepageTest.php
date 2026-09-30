<?php

test('the public homepage presents the job portal and uses live domain data', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Find a job that moves you forward')
        ->assertSee('Popular categories')
        ->assertSee('Featured jobs')
        ->assertSee('Latest jobs')
        ->assertSee('Popular employers')
        ->assertSee('Create candidate account')
        ->assertSee('Create employer account')
        ->assertSee('name="keyword"', false)
        ->assertDontSee('Role matched to your skills');
});
