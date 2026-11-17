<?php

namespace App\Models;

use Database\Factories\MealRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's rating of a meal, 1–5 stars. Only one per person and meal.
 */
#[Fillable(['meal_id', 'user_id', 'rating'])]
class MealRating extends Model
{
    /** @use HasFactory<MealRatingFactory> */
    use HasFactory;

    public const MIN = 1;

    public const MAX = 5;

    /**
     * @return BelongsTo<Meal, $this>
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }
}
