<?php

namespace App\Http\Requests;

use App\Models\ListItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof ListItem && $this->user()->can('update', $item);
    }

    protected function prepareForValidation(): void
    {
        $estimate = $this->input('edit_estimate');
        $description = trim((string) $this->input('edit_description'));

        $this->merge([
            'edit_name' => trim((string) $this->input('edit_name')),
            'edit_description' => $description === '' ? null : $description,
            'edit_estimate' => $estimate === null || $estimate === '' ? null : $estimate,
            'edit_private' => $this->boolean('edit_private'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'edit_name' => ['required', 'string', 'max:255'],
            'edit_description' => ['nullable', 'string', 'max:10000'],
            'edit_estimate' => ['nullable', 'integer'],
            'edit_private' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'edit_name' => __('lists.name'),
            'edit_description' => __('lists.description'),
            'edit_estimate' => __('lists.estimate'),
        ];
    }

    public function estimate(): ?int
    {
        $estimate = $this->validated('edit_estimate');

        return $estimate === null ? null : (int) $estimate;
    }
}
