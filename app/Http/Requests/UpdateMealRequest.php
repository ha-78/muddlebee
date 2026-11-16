<?php

namespace App\Http\Requests;

use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meal = $this->route('meal');

        return $meal instanceof Meal && $this->user()->can('update', $meal);
    }

    protected function prepareForValidation(): void
    {
        $recipeLink = trim((string) $this->input('recipe_link'));
        $comment = trim((string) $this->input('comment'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'recipe_link' => $recipeLink === '' ? null : $recipeLink,
            'comment' => $comment === '' ? null : $comment,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'recipe_link' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'comment' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
