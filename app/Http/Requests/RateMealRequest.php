<?php

namespace App\Http\Requests;

use App\Models\Meal;
use App\Models\MealRating;
use Illuminate\Foundation\Http\FormRequest;

class RateMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meal = $this->route('meal');

        return $meal instanceof Meal && $this->user()->can('rate', $meal);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:'.MealRating::MIN.','.MealRating::MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rating' => __('meals.rating'),
        ];
    }
}
