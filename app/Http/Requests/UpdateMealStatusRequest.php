<?php

namespace App\Http\Requests;

use App\Enums\MealStatus;
use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMealStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meal = $this->route('meal');

        return $meal instanceof Meal && $this->user()->can('move', $meal);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(MealStatus::class)],
        ];
    }

    public function target(): MealStatus
    {
        return $this->enum('status', MealStatus::class);
    }
}
