<?php

use App\Models\User;

it('shows the Muddlebee logo on the welcome page and in the login header', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('aria-label="'.__('layout.brand').'"', false)
        ->assertSee('font-logo', false)
        ->assertSee('fill-logo-mid', false)
        ->assertSee('muddle<span class="text-muted">bee</span>', false);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('aria-label="'.__('layout.brand').'"', false)
        ->assertSee('fill-logo-light', false);
});

it('links the bee favicon on every page', function () {
    $this->get(route('login'))->assertSee('favicon.svg', false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('favicon.svg', false);

    expect(file_exists(public_path('favicon.svg')))->toBeTrue();
});

it('puts the bee left of the section title in every top bar', function () {
    $user = User::factory()->create();

    foreach ([route('home') => __('home.title'), route('threads.index') => __('threads.threads')] as $url => $title) {
        $page = $this->actingAs($user)->get($url)->assertSuccessful();
        $header = str($page->getContent())->after('<header')->before('</header>')->value();

        expect($header)
            ->toContain('fill-logo-mid')
            ->toContain($title)
            ->not->toContain('font-logo')
            ->and(strpos($header, 'fill-logo-mid'))->toBeLessThan(strpos($header, $title));
    }
});
