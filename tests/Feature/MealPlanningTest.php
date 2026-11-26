<?php

use App\Enums\Feature;
use App\Enums\MealStatus;
use App\Models\CalendarDate;
use App\Models\Context;
use App\Models\Meal;
use App\Models\User;

function plannedMeal(User $owner, string $date, array $attributes = []): Meal
{
    $day = CalendarDate::query()->firstOrCreate([
        'context_id' => $owner->currentContextId(),
        'date' => $date,
    ]);

    return Meal::factory()->for($owner)->create([
        'context_id' => $owner->currentContextId(),
        'status' => MealStatus::Planned,
        'calendar_date_id' => $day->id,
        'planned_at' => now(),
        ...$attributes,
    ]);
}

function dayRow(User $user, string $date): ?CalendarDate
{
    return CalendarDate::query()
        ->where('context_id', $user->currentContextId())
        ->whereDate('date', $date)
        ->first();
}

beforeEach(function () {
    $this->travelTo('2026-11-16 10:00:00');
});

it('asks guests to log in before planning', function () {
    $meal = Meal::factory()->create(['status' => MealStatus::Shortlist]);

    $this->put(route('meals.plan', $meal), ['date' => '2026-11-17'])->assertRedirect(route('login'));
    $this->delete(route('meals.unplan', $meal))->assertRedirect(route('login'));
    $this->post(route('meals.again', $meal))->assertRedirect(route('login'));
});

it('plans a shortlisted meal on a day and creates the day', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $meal = Meal::factory()->for($user)->create(['name' => 'Lasagne', 'status' => MealStatus::Shortlist]);

    $this->actingAs($user)
        ->from(route('meals.index', ['status' => 'planned']))
        ->put(route('meals.plan', $meal), ['date' => '2026-11-18'])
        ->assertRedirect(route('meals.index', ['status' => 'planned']))
        ->assertSessionHas('status');

    $meal->refresh();
    $day = dayRow($user, '2026-11-18');

    expect($day)->not->toBeNull()
        ->and($meal->status)->toBe(MealStatus::Planned)
        ->and($meal->calendar_date_id)->toBe($day->id)
        ->and($meal->planned_at)->not->toBeNull();
});

it('puts any number of meals on one day, in the order they were planned', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $first = Meal::factory()->for($user)->create(['name' => 'Gröt', 'status' => MealStatus::Shortlist]);
    $second = Meal::factory()->for($user)->create(['name' => 'Tacos', 'status' => MealStatus::Shortlist]);

    $this->actingAs($user)->put(route('meals.plan', $first), ['date' => '2026-11-18']);
    $this->travel(1)->minutes();
    $this->actingAs($user)->put(route('meals.plan', $second), ['date' => '2026-11-18']);

    $day = dayRow($user, '2026-11-18');

    expect(CalendarDate::query()->count())->toBe(1)
        ->and($day->meals->pluck('name')->all())->toBe(['Gröt', 'Tacos']);

    $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'planned']))
        ->assertSuccessful()
        ->assertSeeInOrder([$day->heading, 'Gröt', 'Tacos']);
});

it('moves a planned meal to another day and removes the empty old day', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $meal = plannedMeal($user, '2026-11-17');
    $stays = plannedMeal($user, '2026-11-19');
    $other = plannedMeal($user, '2026-11-19');

    $this->actingAs($user)
        ->put(route('meals.plan', $meal), ['date' => '2026-11-19'])
        ->assertRedirect();

    expect(dayRow($user, '2026-11-17'))->toBeNull()
        ->and($meal->refresh()->calendar_date_id)->toBe(dayRow($user, '2026-11-19')->id);

    $this->actingAs($user)->put(route('meals.plan', $stays), ['date' => '2026-11-20']);

    expect(dayRow($user, '2026-11-19'))->not->toBeNull()
        ->and($other->refresh()->calendar_date_id)->toBe(dayRow($user, '2026-11-19')->id);
});

it('takes a meal off its day, back to the shortlist, and removes the empty day', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $meal = plannedMeal($user, '2026-11-17');

    $this->actingAs($user)
        ->delete(route('meals.unplan', $meal))
        ->assertRedirect()
        ->assertSessionHas('status');

    $meal->refresh();

    expect($meal->status)->toBe(MealStatus::Shortlist)
        ->and($meal->calendar_date_id)->toBeNull()
        ->and($meal->planned_at)->toBeNull()
        ->and(dayRow($user, '2026-11-17'))->toBeNull();
});

it('keeps a day that still has a notice when its last meal leaves', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $meal = plannedMeal($user, '2026-11-17');
    dayRow($user, '2026-11-17')->notices()->create(['user_id' => $user->id, 'body' => 'Tandläkare', 'is_public' => true]);

    $this->actingAs($user)->delete(route('meals.unplan', $meal));

    expect(dayRow($user, '2026-11-17'))->not->toBeNull();
});

it('plans only shortlisted or planned meals', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $wish = Meal::factory()->for($user)->create(['status' => MealStatus::Wishlist]);
    $discarded = Meal::factory()->for($user)->create(['status' => MealStatus::Discarded]);

    foreach ([$wish, $discarded] as $meal) {
        $this->actingAs($user)
            ->put(route('meals.plan', $meal), ['date' => '2026-11-18'])
            ->assertSessionHasErrors('meal');

        expect($meal->refresh()->calendar_date_id)->toBeNull();
    }

    $this->actingAs($user)
        ->delete(route('meals.unplan', $wish))
        ->assertSessionHasErrors('meal');

    expect(CalendarDate::query()->count())->toBe(0);
});

it('refuses past or malformed days', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $meal = Meal::factory()->for($user)->create(['status' => MealStatus::Shortlist]);

    foreach (['2026-11-15', '18/11/2026', ''] as $date) {
        $this->actingAs($user)
            ->put(route('meals.plan', $meal), ['date' => $date, 'form' => 'pick-day', 'meal_id' => $meal->id])
            ->assertSessionHasErrors('date');
    }

    $this->actingAs($user)
        ->put(route('meals.plan', $meal), ['date' => '2026-11-16'])
        ->assertSessionHasNoErrors();
});

it('lets only those with the planning permission plan, in their own household', function () {
    $member = User::factory()->create();
    $planner = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $elsewhere = Context::query()->create(['name' => 'Annan']);
    $mine = Meal::factory()->for($member)->create(['status' => MealStatus::Shortlist]);
    $theirs = Meal::factory()->create(['status' => MealStatus::Shortlist, 'context_id' => $elsewhere->id]);

    $this->actingAs($member)->put(route('meals.plan', $mine), ['date' => '2026-11-18'])->assertForbidden();
    $this->actingAs($planner)->put(route('meals.plan', $theirs), ['date' => '2026-11-18'])->assertForbidden();

    $planned = plannedMeal($member, '2026-11-18');
    $this->actingAs($member)->delete(route('meals.unplan', $planned))->assertForbidden();

    expect($mine->refresh()->status)->toBe(MealStatus::Shortlist)
        ->and($theirs->refresh()->status)->toBe(MealStatus::Shortlist);
});

it('answers JSON requests without a redirect', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals)->create();
    $meal = Meal::factory()->for($user)->create(['status' => MealStatus::Shortlist]);

    $this->actingAs($user)->putJson(route('meals.plan', $meal), ['date' => '2026-11-18'])->assertNoContent();
    $this->actingAs($user)->putJson(route('meals.plan', $meal), ['date' => '2026-11-01'])->assertUnprocessable();
    $this->actingAs($user)->deleteJson(route('meals.unplan', $meal))->assertNoContent();
    $this->actingAs($user)->deleteJson(route('meals.unplan', $meal))->assertUnprocessable();
});

it('shows the next ten days with their meals, the shortlist, later and earlier meals', function () {
    $user = User::factory()->withFeatures(Feature::PlanMeals, Feature::AddMealsToWishlist)->create();
    Meal::factory()->for($user)->create(['name' => 'Kortad pasta', 'status' => MealStatus::Shortlist]);
    plannedMeal($user, '2026-11-17', ['name' => 'Morgondagens soppa']);
    plannedMeal($user, '2026-12-10', ['name' => 'Julbord']);
    plannedMeal($user, '2026-11-10', ['name' => 'Förra veckans fisk']);

    $response = $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'planned', 'date' => '2026-11-17']))
        ->assertSuccessful()
        ->assertSee('id="day-2026-11-16"', false)
        ->assertSee('id="day-2026-11-25"', false)
        ->assertDontSee('id="day-2026-11-26"', false)
        ->assertSee('Morgondagens soppa')
        ->assertSee('Kortad pasta')
        ->assertSee(__('meals.pick_meal'))
        ->assertSee(__('meals.pick_day'))
        ->assertSee(__('meals.move'))
        ->assertSee(__('meals.unplan'))
        ->assertSee('action="'.route('meals.unplan', Meal::query()->where('name', 'Morgondagens soppa')->first()).'"', false)
        ->assertSeeInOrder([__('meals.later'), 'Julbord', __('meals.earlier'), 'Förra veckans fisk'])
        ->assertSee(__('meals.wish_again'))
        ->assertSee('action="'.route('meals.again', Meal::query()->where('name', 'Förra veckans fisk')->first()).'"', false);

    expect($response->getContent())->toMatch('/<section[^>]*id="day-2026-11-17"[^>]*ring-2|<section[^>]*ring-2[^>]*id="day-2026-11-17"/');
});

it('shows the planner read-only to those who may not plan', function () {
    $user = User::factory()->create();
    plannedMeal($user, '2026-11-17', ['name' => 'Morgondagens soppa']);

    $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'planned']))
        ->assertSuccessful()
        ->assertSee('Morgondagens soppa')
        ->assertDontSee(__('meals.move'))
        ->assertDontSee(__('meals.unplan'))
        ->assertDontSee(__('meals.pick_meal'));
});

it('offers planning for the shortlist on the wishlist tab', function () {
    $planner = User::factory()->withFeatures(Feature::PlanMeals)->create();
    Meal::factory()->for($planner)->create(['name' => 'Kortad pasta', 'status' => MealStatus::Shortlist]);

    $this->actingAs($planner)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee('Kortad pasta')
        ->assertSee(__('meals.plan'))
        ->assertSee(__('meals.pick_day'));

    $this->actingAs(User::factory()->create())
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee('Kortad pasta')
        ->assertDontSee(__('meals.pick_day'));
});

it('wishes an earlier planned meal again', function () {
    $user = User::factory()->withFeatures(Feature::AddMealsToWishlist)->create();
    $old = plannedMeal($user, '2026-11-10', ['name' => 'Fisk', 'recipe_link' => 'https://example.com/fisk', 'comment' => 'Gott']);

    $this->actingAs($user)
        ->post(route('meals.again', $old))
        ->assertRedirect()
        ->assertSessionHas('status');

    $copy = Meal::query()->whereKeyNot($old->id)->sole();

    expect($copy->name)->toBe('Fisk')
        ->and($copy->recipe_link)->toBe('https://example.com/fisk')
        ->and($copy->comment)->toBe('Gott')
        ->and($copy->status)->toBe(MealStatus::Wishlist)
        ->and($old->refresh()->status)->toBe(MealStatus::Planned);

    $this->actingAs(User::factory()->create())->post(route('meals.again', $old))->assertForbidden();
});

it('shows planned meals in the start page boxes', function () {
    $user = User::factory()->create();
    plannedMeal($user, '2026-11-16', ['name' => 'Dagens gryta']);
    plannedMeal($user, '2026-11-20', ['name' => 'Fredagspizza']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Dagens gryta', 'Fredagspizza']);
});

it('lets planners drag meals between days and the shortlist', function () {
    $planner = User::factory()->withFeatures(Feature::PlanMeals)->create();
    Meal::factory()->for($planner)->create(['name' => 'Kortad pasta', 'status' => MealStatus::Shortlist]);
    $meal = plannedMeal($planner, '2026-11-17', ['name' => 'Morgondagens soppa']);

    $this->actingAs($planner)
        ->get(route('meals.index', ['status' => 'planned']))
        ->assertSuccessful()
        ->assertSee('x-data="mealPlanner"', false)
        ->assertSee('data-zone="2026-11-17"', false)
        ->assertSee('data-zone=""', false)
        ->assertSee('x-sort:group="meals"', false)
        ->assertSee('x-sort:handle', false)
        // Only the grip starts a drag, also on empty days (the placeholder text can't be dragged).
        ->assertSee('x-sort:config="{ sort: false, handle: \'[x-sort\\\\:handle]\' }"', false)
        ->assertDontSee('x-sort:config="{ sort: false }"', false)
        ->assertSee('data-plan-url="'.route('meals.plan', $meal).'"', false)
        ->assertSee('data-unplan-url="'.route('meals.unplan', $meal).'"', false)
        ->assertSee(__('meals.drag_hint'));

    $this->actingAs(User::factory()->create())
        ->get(route('meals.index', ['status' => 'planned']))
        ->assertSuccessful()
        ->assertSee('Morgondagens soppa')
        ->assertDontSee('x-sort:group', false)
        ->assertDontSee('x-sort:handle', false);
});
