<?php

use App\Enums\Feature;
use App\Models\User;

it('shows the signed-in user on the home page', function () {
    $user = User::factory()->withFeatures(Feature::CreateThreads)->create(['name' => 'Hanna Anderberg']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee(__('home.welcome').',')
        ->assertSee(route('account'), false)
        ->assertSee(__('users.account'), false)
        ->assertDontSee(__('users.log_out'))
        ->assertDontSee(__('themes.label'))
        ->assertSee(__('threads.new_thread'))
        ->assertSee('name="return_to" value="home"', false)
        ->assertSee('href="'.route('threads.index').'"', false)
        ->assertDontSee('var(--color-', false);
});

it('asks guests to log in before home', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

it('shows today, tomorrow, and the eight days after', function () {
    $this->travelTo('2026-10-27 15:00:00');

    $today = now()->timezone(config('app.timezone'))->locale(config('app.locale'));
    $tomorrow = $today->copy()->addDay();
    $upcoming = collect(range(2, 9))->map(fn (int $offset) => $today->copy()->addDays($offset));
    $later = $today->copy()->addDays(5);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            __('calendar.today'),
            $today->isoFormat('dddd'),
            $today->toDateString(),
            $today->isoFormat('D'),
            $today->isoFormat('MMMM'),
            __('calendar.tomorrow'),
            $tomorrow->isoFormat('dddd'),
            $tomorrow->toDateString(),
            $tomorrow->isoFormat('D'),
            $tomorrow->isoFormat('MMMM'),
            ...$upcoming->flatMap(fn ($date) => $date->toDateString() === $later->toDateString()
                ? [$date->toDateString(), $date->isoFormat('D'), $date->isoFormat('dddd'), $date->isoFormat('MMMM')]
                : [$date->toDateString()])->all(),
        ])
        ->assertSee('data-color="b"', false)
        ->assertSee(__('calendar.no_notices'))
        ->assertDontSee(__('calendar.plan_meal'));
});

it('offers meal planning from each day to those who may plan', function () {
    $this->travelTo('2026-10-27 15:00:00');
    $planner = User::factory()->withFeatures(Feature::PlanMeals)->create();

    $response = $this->actingAs($planner)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee(__('calendar.plan_meal'))
        ->assertSee(__('calendar.plan_short'))
        ->assertSee('href="'.e(route('meals.index', ['status' => 'planned', 'date' => '2026-10-27'])).'"', false)
        ->assertSee('href="'.e(route('meals.index', ['status' => 'planned', 'date' => '2026-11-05'])).'"', false);

    expect(substr_count($response->getContent(), 'status=planned&amp;date='))->toBe(10);
});
