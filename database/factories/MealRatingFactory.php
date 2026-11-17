<?php

namespace Database\Factories;

use App\Models\Meal;
use App\Models\MealRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealRating>
 */
class MealRatingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_id' => Meal::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(MealRating::MIN, MealRating::MAX),
        ];
    }
}
