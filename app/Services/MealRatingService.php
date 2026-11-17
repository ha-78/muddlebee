<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\MealRating;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Ratings (1–5 stars) on wishlist meals: one per person and meal, which can be changed or removed.
 */
class MealRatingService
{
    /**
     * Unrated meals show this many stars, and count as this much when sorting.
     */
    public const DEFAULT_STARS = 3.0;

    public function rate(User $actor, Meal $meal, int $rating): MealRating
    {
        if (! $actor->can('rate', $meal)) {
            throw new AuthorizationException;
        }

        return MealRating::query()->updateOrCreate(
            ['meal_id' => $meal->getKey(), 'user_id' => $actor->getKey()],
            ['rating' => max(MealRating::MIN, min(MealRating::MAX, $rating))],
        );
    }

    public function unrate(User $actor, Meal $meal): void
    {
        if (! $actor->can('rate', $meal)) {
            throw new AuthorizationException;
        }

        $meal->ratings()->where('user_id', $actor->getKey())->delete();
    }

    /**
     * The meal's ratings as the stars show them: the exact average, the number of ratings,
     * the stars to draw (nearest half, or the default when unrated) and the user's own rating.
     *
     * @return array{average: float|null, count: int, stars: float, rating: int|null}
     */
    public function summary(Meal $meal, ?User $user = null): array
    {
        $count = $meal->ratings()->count();
        $average = $count > 0 ? round((float) $meal->ratings()->avg('rating'), 2) : null;

        return [
            'average' => $average,
            'count' => $count,
            'stars' => self::stars($average),
            'rating' => $user === null ? null : $meal->ratings()->where('user_id', $user->getKey())->value('rating'),
        ];
    }

    /**
     * An average rounded to the nearest half star; the default for an unrated meal.
     */
    public static function stars(?float $average): float
    {
        return $average === null ? self::DEFAULT_STARS : round($average * 2) / 2;
    }
}
