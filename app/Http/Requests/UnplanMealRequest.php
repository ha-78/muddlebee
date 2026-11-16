<?php

namespace App\Http\Requests;

use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;

class UnplanMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meal = $this->route('meal');

        return $meal instanceof Meal && $this->user()->can('plan', $meal);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
