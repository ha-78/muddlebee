<?php

namespace App\Http\Requests;

use App\Models\Meal;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class PlanMealRequest extends FormRequest
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
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => __('meals.date'),
        ];
    }

    public function day(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->validated('date'), config('app.timezone'))->startOfDay();
    }
}
