<?php

use App\Enums\Feature;
use App\Enums\MealStatus;
use App\Models\Meal;
use App\Models\MealRating;
use App\Models\User;
use App\Services\MealService;

function wishlistMeal(User $user, string $name): Meal
{
    return app(MealService::class)->list($user, MealStatus::Wishlist)->firstWhere('name', $name);
}

it('draws whole and half stars for a value', function () {
    $html = (string) $this->blade('<x-meals.stars :value="3.5" />');

    expect(substr_count($html, 'text-line'))->toBe(5)
        ->and(substr_count($html, '[clip-path:inset(0_50%_0_0)]'))->toBe(1)
        ->and(preg_match_all('/class="absolute inset-0 size-full text-accent hidden"/', $html))->toBe(1)
        ->and(preg_match_all('/class="absolute inset-0 size-full text-accent"/', $html))->toBe(3)
        ->and($html)->toContain('aria-hidden="true"')
        ->not->toContain('opacity-45');
});

it('makes the stars pale for an unrated meal and follows mealRating when live', function () {
    $html = (string) $this->blade('<x-meals.stars :value="3" muted live />');

    expect($html)->toContain('opacity-45')
        ->toContain("x-bind:class=\"{ 'opacity-45': unrated }\"")
        ->toContain('stars &lt; 4.5');
});

it('summarises a rated and an unrated meal', function () {
    $user = User::factory()->create();
    $rated = Meal::factory()->create(['name' => 'Tacos']);
    Meal::factory()->create(['name' => 'Soppa']);
    MealRating::factory()->for($rated)->create(['rating' => 4]);
    MealRating::factory()->for($rated)->create(['rating' => 5]);

    $this->actingAs($user);

    $this->blade('<x-meals.rating-summary :meal="$meal" />', ['meal' => wishlistMeal($user, 'Tacos')])
        ->assertSee(trans_choice('meals.rating_count', 2, ['count' => 2]))
        ->assertSee(__('meals.rating_average', ['average' => '4,5']))
        ->assertDontSee('gap-px opacity-45', false);

    $this->blade('<x-meals.rating-summary :meal="$meal" />', ['meal' => wishlistMeal($user, 'Soppa')])
        ->assertSee(__('meals.rating_none'))
        ->assertSee('gap-px opacity-45', false);
});

it('offers five stars to pick, the own rating checked, and Ta bort mitt betyg', function () {
    $user = User::factory()->withFeatures(Feature::RateMeals)->create();
    $meal = Meal::factory()->create(['name' => 'Tacos']);
    MealRating::factory()->for($meal)->for($user)->create(['rating' => 2]);

    $this->actingAs($user);

    $html = (string) $this->blade('<x-meals.rating-scope :meal="$meal"><x-meals.rate :meal="$meal" /></x-meals.rating-scope>', ['meal' => wishlistMeal($user, 'Tacos')]);

    expect(substr_count($html, 'type="radio"'))->toBe(5)
        ->and(substr_count($html, 'checked'))->toBeGreaterThanOrEqual(1)
        ->and($html)->toContain('value="2"')
        ->toContain('x-data="mealRating"')
        ->toContain('data-url="'.route('meals.rating', $meal).'"')
        ->toContain('data-rating="2"')
        ->toContain(__('meals.my_rating'))
        ->toContain(__('meals.remove_rating'))
        ->toContain(__('meals.rate_stars', ['count' => 5]));
});

it('shows the average under each wishlist meal and the stars to pick for raters', function () {
    $rater = User::factory()->withFeatures(Feature::RateMeals)->create();
    $rated = Meal::factory()->create(['name' => 'Tacos']);
    Meal::factory()->create(['name' => 'Soppa']);
    MealRating::factory()->for($rated)->create(['rating' => 4]);
    MealRating::factory()->for($rated)->for($rater)->create(['rating' => 5]);

    $page = $this->actingAs($rater)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee(trans_choice('meals.rating_count', 2, ['count' => 2]))
        ->assertSee(__('meals.rating_none'))
        ->assertSee(__('meals.my_rating'))
        ->assertSee('data-rating="5"', false);

    expect(substr_count($page->getContent(), 'x-data="mealRating"'))->toBe(2)
        ->and(substr_count($page->getContent(), 'type="radio"'))->toBe(10);
});

it('shows the averages but no stars to pick without the feature', function () {
    $member = User::factory()->create();
    $meal = Meal::factory()->create(['name' => 'Tacos']);
    MealRating::factory()->for($meal)->create(['rating' => 4]);

    $this->actingAs($member)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee(trans_choice('meals.rating_count', 1, ['count' => 1]))
        ->assertDontSee(__('meals.my_rating'))
        ->assertDontSee('type="radio"', false);
});

it('offers Nyast and Högst betyg on the wishlist and keeps the tab', function () {
    $user = User::factory()->create();
    Meal::factory()->create();

    $this->actingAs($user)
        ->get(route('meals.index', ['sort' => 'rating']))
        ->assertSuccessful()
        ->assertSee(__('meals.sort_newest'))
        ->assertSee(__('meals.sort_rating'))
        ->assertSee('href="'.e(route('meals.index', ['status' => 'wishlist', 'sort' => 'rating'])).'"', false)
        ->assertSee('aria-current="true"', false);
});

it('shows no ratings on the other tabs', function () {
    $rater = User::factory()->withFeatures(Feature::RateMeals, Feature::AddMealsToShortlist)->create();
    $meal = Meal::factory()->create(['name' => 'Kortad', 'status' => MealStatus::Shortlist]);
    MealRating::factory()->for($meal)->create(['rating' => 5]);

    $this->actingAs($rater)
        ->get(route('meals.index', ['status' => 'shortlist']))
        ->assertSuccessful()
        ->assertSee('Kortad')
        ->assertDontSee('x-data="mealRating"', false)
        ->assertDontSee(trans_choice('meals.rating_count', 1, ['count' => 1]))
        ->assertDontSee(__('meals.rating_none'))
        ->assertDontSee(__('meals.sort_rating'));
});
