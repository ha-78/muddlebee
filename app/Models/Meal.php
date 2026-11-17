<?php

namespace App\Models;

use App\Enums\MealStatus;
use App\Services\MealRatingService;
use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['context_id', 'user_id', 'name', 'recipe_link', 'comment', 'status', 'calendar_date_id', 'planned_at'])]
class Meal extends Model
{
    /** @use HasFactory<MealFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'wishlist',
    ];

    /**
     * Everyone's ratings of this meal. Kept whatever the status; only the wishlist shows them.
     *
     * @return HasMany<MealRating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(MealRating::class);
    }

    /**
     * The average rating as stars, rounded to the nearest half; 3 for a meal nobody has rated.
     * Needs the wishlist query (withAvg on ratings).
     */
    public function averageStars(): float
    {
        $average = $this->ratings_avg_rating;

        return MealRatingService::stars($average === null ? null : (float) $average);
    }

    public function isUnrated(): bool
    {
        return (int) ($this->ratings_count ?? 0) === 0;
    }

    /**
     * The signed-in user's own rating, from the wishlist query; null when they haven't rated.
     */
    public function myRating(): ?int
    {
        return $this->my_rating === null ? null : (int) $this->my_rating;
    }

    public function hasDetails(): bool
    {
        return filled($this->recipe_link) || filled($this->comment);
    }

    /**
     * Meals in the user's household.
     *
     * @param  Builder<Meal>  $query
     * @return Builder<Meal>
     */
    public function scopeForContext(Builder $query, User $user): Builder
    {
        return $query->where($query->qualifyColumn('context_id'), $user->currentContextId());
    }

    /**
     * The day a planned meal is on.
     *
     * @return BelongsTo<CalendarDate, $this>
     */
    public function calendarDate(): BelongsTo
    {
        return $this->belongsTo(CalendarDate::class);
    }

    /**
     * @return BelongsTo<Context, $this>
     */
    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
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
            'status' => MealStatus::class,
            'planned_at' => 'datetime',
        ];
    }

    /**
     * What the "Ändra måltid" sheet needs to edit this meal.
     *
     * @return array{id: int, action: string, name: string, recipe_link: string, comment: string}
     */
    public function toEditor(): array
    {
        return [
            'id' => $this->getKey(),
            'action' => route('meals.update', $this),
            'name' => $this->name,
            'recipe_link' => $this->recipe_link ?? '',
            'comment' => $this->comment ?? '',
        ];
    }
}
