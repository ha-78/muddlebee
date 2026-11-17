<?php

use App\Enums\Feature;
use App\Models\Meal;
use App\Models\MealRating;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('stores one rating per person and meal', function () {
    $meal = Meal::factory()->create();
    $user = User::factory()->create();

    MealRating::factory()->for($meal)->for($user)->create(['rating' => 4]);

    expect($meal->ratings()->sole()->rating)->toBe(4)
        ->and($user->mealRatings()->count())->toBe(1);

    MealRating::factory()->for($meal)->for($user)->create(['rating' => 2]);
})->throws(UniqueConstraintViolationException::class);

it('lets different people rate the same meal', function () {
    $meal = Meal::factory()->create();

    MealRating::factory()->for($meal)->count(3)->create();

    expect($meal->ratings()->count())->toBe(3);
});

it('removes ratings with their meal or their user', function () {
    $meal = Meal::factory()->create();
    $other = Meal::factory()->create();
    $user = User::factory()->create();
    MealRating::factory()->for($meal)->for($user)->create();
    MealRating::factory()->for($other)->for($user)->create();
    MealRating::factory()->for($other)->create();

    $meal->delete();

    expect(MealRating::query()->count())->toBe(2);

    $user->delete();

    expect(MealRating::query()->count())->toBe(1)
        ->and(MealRating::query()->sole()->meal_id)->toBe($other->id);
});

it('names the feature rate-meals and keeps old vote-on-meals grants', function () {
    expect(Feature::RateMeals->value)->toBe('rate-meals')
        ->and(Feature::RateMeals->label())->toBe(__('users.features.rate-meals'));

    $user = User::factory()->create();
    $membership = $user->contextMembership();

    DB::table('user_features')->insert([
        'context_user_id' => $membership->id,
        'feature' => 'vote-on-meals',
        'granted' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_11_17_140100_rename_vote_on_meals_feature.php');
    $migration->up();

    expect(DB::table('user_features')->where('feature', 'vote-on-meals')->exists())->toBeFalse()
        ->and($user->fresh()->allows(Feature::RateMeals))->toBeTrue();
});

function wish(array $attributes = []): Meal
{
    return Meal::factory()->create(['status' => App\Enums\MealStatus::Wishlist, ...$attributes]);
}

it('asks guests to log in before rating', function () {
    $meal = wish();

    $this->put(route('meals.rating', $meal), ['rating' => 4])->assertRedirect(route('login'));
    $this->delete(route('meals.rating.destroy', $meal))->assertRedirect(route('login'));
});

it('rates a wishlist meal, changes the rating and removes it', function () {
    $user = User::factory()->withFeatures(Feature::RateMeals)->create();
    $meal = wish(['name' => 'Lasagne']);

    $this->actingAs($user)
        ->from(route('meals.index'))
        ->put(route('meals.rating', $meal), ['rating' => 4])
        ->assertRedirect(route('meals.index'))
        ->assertSessionHas('status', __('meals.rated', ['name' => 'Lasagne']));

    $this->actingAs($user)->put(route('meals.rating', $meal), ['rating' => 2])->assertRedirect();

    expect($meal->ratings()->sole()->rating)->toBe(2);

    $this->actingAs($user)
        ->delete(route('meals.rating.destroy', $meal))
        ->assertRedirect()
        ->assertSessionHas('status', __('meals.unrated', ['name' => 'Lasagne']));

    expect($meal->ratings()->count())->toBe(0);
});

it('accepts only whole ratings from 1 to 5', function () {
    $user = User::factory()->withFeatures(Feature::RateMeals)->create();
    $meal = wish();

    foreach ([0, 6, 3.5, 'fem', null] as $rating) {
        $this->actingAs($user)
            ->put(route('meals.rating', $meal), ['rating' => $rating])
            ->assertSessionHasErrors('rating');
    }

    expect($meal->ratings()->count())->toBe(0);
});

it('lets only those with the feature rate, in their own household, on the wishlist', function () {
    $member = User::factory()->create();
    $reader = User::factory()->withRole(App\Enums\Role::Reader)->create();
    $rater = User::factory()->withFeatures(Feature::RateMeals)->create();
    $meal = wish();
    $elsewhere = wish(['context_id' => App\Models\Context::query()->create(['name' => 'Annan'])->id]);

    $this->actingAs($member)->put(route('meals.rating', $meal), ['rating' => 4])->assertForbidden();
    $this->actingAs($reader)->put(route('meals.rating', $meal), ['rating' => 4])->assertForbidden();
    $this->actingAs($rater)->put(route('meals.rating', $elsewhere), ['rating' => 4])->assertForbidden();

    foreach ([App\Enums\MealStatus::Shortlist, App\Enums\MealStatus::Planned, App\Enums\MealStatus::Discarded] as $status) {
        $other = Meal::factory()->create(['status' => $status]);

        $this->actingAs($rater)->put(route('meals.rating', $other), ['rating' => 4])->assertForbidden();
        $this->actingAs($rater)->delete(route('meals.rating.destroy', $other))->assertForbidden();
    }

    expect(MealRating::query()->count())->toBe(0);
});

it('keeps ratings when a meal leaves the wishlist, and lets them be changed again when it returns', function () {
    $user = User::factory()->withFeatures(Feature::RateMeals)->create();
    $meal = wish();
    MealRating::factory()->for($meal)->for($user)->create(['rating' => 5]);

    $meal->update(['status' => App\Enums\MealStatus::Shortlist]);

    $this->actingAs($user)->delete(route('meals.rating.destroy', $meal))->assertForbidden();

    expect($meal->ratings()->count())->toBe(1);

    $meal->update(['status' => App\Enums\MealStatus::Wishlist]);

    $this->actingAs($user)->put(route('meals.rating', $meal), ['rating' => 3])->assertRedirect();

    expect($meal->ratings()->sole()->rating)->toBe(3);
});

it('answers JSON requests with the new average, count, stars and own rating', function () {
    $user = User::factory()->withFeatures(Feature::RateMeals)->create();
    $meal = wish();
    MealRating::factory()->for($meal)->create(['rating' => 4]);
    MealRating::factory()->for($meal)->create(['rating' => 5]);

    $this->actingAs($user)
        ->putJson(route('meals.rating', $meal), ['rating' => 5])
        ->assertOk()
        ->assertExactJson(['average' => 4.67, 'count' => 3, 'stars' => 4.5, 'rating' => 5]);

    $this->actingAs($user)
        ->deleteJson(route('meals.rating.destroy', $meal))
        ->assertOk()
        ->assertExactJson(['average' => 4.5, 'count' => 2, 'stars' => 4.5, 'rating' => null]);

    $this->actingAs($user)->putJson(route('meals.rating', $meal), ['rating' => 9])->assertUnprocessable();
});

it('shows three stars for a meal nobody has rated', function () {
    $meal = wish();

    expect(app(App\Services\MealRatingService::class)->summary($meal))
        ->toBe(['average' => null, 'count' => 0, 'stars' => 3.0, 'rating' => null]);
});

it('loads the average, count and own rating for wishlist meals', function () {
    $user = User::factory()->create();
    $rated = wish(['name' => 'Tacos']);
    $unrated = wish(['name' => 'Soppa']);
    MealRating::factory()->for($rated)->for($user)->create(['rating' => 4]);
    MealRating::factory()->for($rated)->create(['rating' => 5]);
    MealRating::factory()->for($rated)->create(['rating' => 2]);

    $meals = app(App\Services\MealService::class)
        ->list($user, App\Enums\MealStatus::Wishlist)
        ->keyBy('name');

    expect((float) $meals['Tacos']->ratings_avg_rating)->toBeGreaterThan(3.66)->toBeLessThan(3.67)
        ->and($meals['Tacos']->ratings_count)->toBe(3)
        ->and($meals['Tacos']->averageStars())->toBe(3.5)
        ->and($meals['Tacos']->myRating())->toBe(4)
        ->and($meals['Tacos']->isUnrated())->toBeFalse()
        ->and($meals['Soppa']->averageStars())->toBe(3.0)
        ->and($meals['Soppa']->isUnrated())->toBeTrue()
        ->and($meals['Soppa']->myRating())->toBeNull();
});

it('sorts the wishlist by rating, unrated meals counting as three', function () {
    $user = User::factory()->create();
    // Created best first, so newest first is the reverse of the rating order.
    $highMany = wish(['name' => 'Hög och många']);
    $high = wish(['name' => 'Hög']);
    $unrated = wish(['name' => 'Ingen']);
    $low = wish(['name' => 'Låg']);
    MealRating::factory()->for($low)->create(['rating' => 2]);
    MealRating::factory()->for($high)->create(['rating' => 5]);
    MealRating::factory()->for($highMany)->count(2)->create(['rating' => 5]);

    $service = app(App\Services\MealService::class);

    expect($service->list($user, App\Enums\MealStatus::Wishlist, App\Services\MealService::SORT_RATING)->pluck('name')->all())
        ->toBe(['Hög och många', 'Hög', 'Ingen', 'Låg'])
        ->and($service->list($user, App\Enums\MealStatus::Wishlist)->pluck('name')->all())
        ->toBe(['Låg', 'Ingen', 'Hög', 'Hög och många']);

    $this->actingAs($user)
        ->get(route('meals.index', ['sort' => 'rating']))
        ->assertSuccessful()
        ->assertViewHas('sort', 'rating');

    $this->actingAs($user)
        ->get(route('meals.index', ['sort' => 'nonsense']))
        ->assertViewHas('sort', 'newest');
});

it('loads no ratings for the other tabs', function () {
    $user = User::factory()->create();
    $meal = Meal::factory()->create(['status' => App\Enums\MealStatus::Shortlist]);
    MealRating::factory()->for($meal)->create(['rating' => 5]);

    $shortlist = app(App\Services\MealService::class)->list($user, App\Enums\MealStatus::Shortlist);

    expect($shortlist->sole()->getAttributes())
        ->not->toHaveKey('ratings_avg_rating')
        ->not->toHaveKey('ratings_count')
        ->not->toHaveKey('my_rating');
});
