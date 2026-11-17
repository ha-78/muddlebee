<?php

namespace App\Http\Requests;

use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;

class UnrateMealRequest extends FormRequest
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
        return [];
    }
}
