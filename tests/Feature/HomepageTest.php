<?php

test('the public homepage presents the Staff of Rajasthan job portal experience', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Find Your Next Job')
        ->assertSee('Popular categories')
        ->assertSee('Featured jobs')
        ->assertSee('Latest jobs')
        ->assertSee('Popular companies')
        ->assertSee('Create candidate account')
        ->assertSee('Create employer account')
        ->assertDontSee('Let&#039;s get started', false);
});
