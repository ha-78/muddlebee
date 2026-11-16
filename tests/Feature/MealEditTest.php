<?php

use App\Enums\Feature;
use App\Enums\MealStatus;
use App\Enums\Role;
use App\Models\CalendarDate;
use App\Models\Context;
use App\Models\Meal;
use App\Models\User;

beforeEach(function () {
    $this->travelTo('2026-11-16 10:00:00');
});

it('asks guests to log in before editing a meal', function () {
    $meal = Meal::factory()->create();

    $this->patch(route('meals.update', $meal), ['name' => 'Ny'])->assertRedirect(route('login'));
});

it('lets the one who added a meal change its name, link and comment', function () {
    $author = User::factory()->create();
    $meal = Meal::factory()->for($author)->create([
        'name' => 'Pasta',
        'recipe_link' => 'https://example.com/gammal',
        'comment' => 'Gammal',
        'status' => MealStatus::Shortlist,
    ]);

    $this->actingAs($author)
        ->from(route('meals.index', ['status' => 'shortlist']))
        ->patch(route('meals.update', $meal), [
            'form' => 'edit-meal',
            'meal_id' => $meal->id,
            'name' => '  Pasta carbonara ',
            'recipe_link' => 'https://example.com/ny',
            'comment' => '',
        ])
        ->assertRedirect(route('meals.index', ['status' => 'shortlist']))
        ->assertSessionHas('status', __('meals.updated'));

    $meal->refresh();

    expect($meal->name)->toBe('Pasta carbonara')
        ->and($meal->recipe_link)->toBe('https://example.com/ny')
        ->and($meal->comment)->toBeNull()
        ->and($meal->status)->toBe(MealStatus::Shortlist);
});

it('keeps a planned meal on its day when it is edited', function () {
    $author = User::factory()->create();
    $day = CalendarDate::query()->create(['context_id' => $author->currentContextId(), 'date' => '2026-11-18']);
    $meal = Meal::factory()->for($author)->create([
        'context_id' => $author->currentContextId(),
        'status' => MealStatus::Planned,
        'calendar_date_id' => $day->id,
        'planned_at' => now(),
    ]);

    $this->actingAs($author)->patch(route('meals.update', $meal), ['name' => 'Tacos'])->assertRedirect();

    expect($meal->refresh()->name)->toBe('Tacos')
        ->and($meal->status)->toBe(MealStatus::Planned)
        ->and($meal->calendar_date_id)->toBe($day->id);
});

it('lets admins edit anyone\'s meal, but not other members or readers', function () {
    $author = User::factory()->create();
    $member = User::factory()->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $meal = Meal::factory()->for($author)->create(['name' => 'Soppa']);

    $this->actingAs($member)->patch(route('meals.update', $meal), ['name' => 'Fel'])->assertForbidden();
    $this->actingAs($reader)->patch(route('meals.update', $meal), ['name' => 'Fel'])->assertForbidden();

    expect($meal->refresh()->name)->toBe('Soppa');

    $this->actingAs($admin)->patch(route('meals.update', $meal), ['name' => 'Rätt'])->assertRedirect();

    expect($meal->refresh()->name)->toBe('Rätt');
});

it('keeps meals in another household out of reach', function () {
    $admin = User::factory()->withRole(Role::Admin)->create();
    $elsewhere = Context::query()->create(['name' => 'Annan']);
    $meal = Meal::factory()->create(['name' => 'Grannens', 'context_id' => $elsewhere->id]);

    $this->actingAs($admin)->patch(route('meals.update', $meal), ['name' => 'Fel'])->assertForbidden();

    expect($meal->refresh()->name)->toBe('Grannens');
});

it('rejects an empty name or a bad link and opens the edit sheet again', function () {
    // With the wishlist card on the page too, to check its fields keep out of the edit errors.
    $author = User::factory()->withFeatures(Feature::AddMealsToWishlist)->create();
    $meal = Meal::factory()->for($author)->create(['name' => 'Pasta']);

    // No assertSessionHasErrors() here: with JSON session serialization it turns the stored errors back into
    // an object, and the next request then loads them as an empty bag. The page below shows the errors instead.
    $this->actingAs($author)
        ->from(route('meals.index'))
        ->patch(route('meals.update', $meal), [
            'form' => 'edit-meal',
            'meal_id' => $meal->id,
            'name' => '   ',
            'recipe_link' => 'inte en länk',
        ])
        ->assertRedirect(route('meals.index'));

    expect($meal->refresh()->name)->toBe('Pasta');

    $this->actingAs($author)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee('x-data="{ open: true }"', false)
        ->assertSee('id="edit-meal-name-error"', false)
        ->assertSee('id="edit-meal-recipe-link-error"', false)
        ->assertDontSee('id="name-error"', false)
        ->assertDontSee('id="recipe_link-error"', false)
        ->assertSee(e(route('meals.update', $meal)), false);
});

it('shows Ändra only on meals the user may edit, on every tab', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    Meal::factory()->for($author)->create(['name' => 'Min önskan']);
    Meal::factory()->for($other)->create(['name' => 'Annans önskan']);
    $day = CalendarDate::query()->create(['context_id' => $author->currentContextId(), 'date' => '2026-11-17']);
    Meal::factory()->for($author)->create([
        'name' => 'Min planerade',
        'status' => MealStatus::Planned,
        'calendar_date_id' => $day->id,
        'planned_at' => now(),
    ]);

    $wishlist = $this->actingAs($author)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee(__('meals.edit_meal'));

    expect(substr_count($wishlist->getContent(), "\$dispatch('edit-meal'"))->toBe(1);

    $planner = $this->actingAs($author)
        ->get(route('meals.index', ['status' => 'planned']))
        ->assertSuccessful();

    expect(substr_count($planner->getContent(), "\$dispatch('edit-meal'"))->toBe(1);

    $this->actingAs(User::factory()->withRole(Role::Reader)->create())
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertDontSee(__('meals.edit_meal'))
        ->assertDontSee("\$dispatch('edit-meal'", false);
});

it('keeps the start page free of meal editing', function () {
    $author = User::factory()->create();
    $day = CalendarDate::query()->create(['context_id' => $author->currentContextId(), 'date' => '2026-11-16']);
    Meal::factory()->for($author)->create([
        'name' => 'Dagens',
        'status' => MealStatus::Planned,
        'calendar_date_id' => $day->id,
        'planned_at' => now(),
    ]);

    $this->actingAs($author)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Dagens')
        ->assertDontSee("\$dispatch('edit-meal'", false);
});
