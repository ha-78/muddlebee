<?php

namespace App\Http\Requests;

use App\Models\ItemList;
use Illuminate\Foundation\Http\FormRequest;

class UpdateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('list');

        if (! $list instanceof ItemList || ! $this->user()->can('update', $list)) {
            return false;
        }

        // Sharing or unsharing needs the right to publish lists.
        return $this->boolean('list_is_public') === (bool) $list->is_public
            || $this->user()->can('publish', ItemList::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'list_name' => trim((string) $this->input('list_name')),
            'list_is_public' => $this->boolean('list_is_public'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'list_name' => ['required', 'string', 'max:255'],
            'list_is_public' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'list_name' => __('lists.name'),
        ];
    }
}
