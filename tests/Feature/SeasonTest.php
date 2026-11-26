<?php

use App\Enums\Season;
use App\Models\User;

it('shows no seasonal decorations when APP_SEASON is empty', function () {
    config(['app.season' => null]);

    expect(Season::current())->toBeNull();

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('data-season', false)
        ->assertDontSee('data-decor', false);
});

it('ignores an unknown season', function () {
    config(['app.season' => 'midsommar']);

    expect(Season::current())->toBeNull();

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertDontSee('data-decor', false);
});

it('decorates the site for Christmas', function () {
    config(['app.season' => ' Christmas ']);

    expect(Season::current())->toBe(Season::Christmas);

    $this->actingAs(User::factory()->create())
        ->get(route('account'))
        ->assertSuccessful()
        ->assertSee('data-season="christmas"', false)
        ->assertSee('data-decor="hat"', false)
        ->assertSee('data-decor="snow"', false)
        ->assertSee('data-decor="lights"', false)
        ->assertSee('data-decor="holly"', false)
        ->assertDontSee('var(--color-', false);

    // The login page gets the hat, the snow and the lights too.
    auth()->logout();

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('data-decor="hat"', false)
        ->assertSee('data-decor="lights"', false);
});

it('puts holly on today\'s and tomorrow\'s date boxes at Christmas', function () {
    config(['app.season' => 'christmas']);

    $content = $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->getContent();

    expect(substr_count($content, 'data-decor="holly"'))->toBeGreaterThanOrEqual(2);
});
