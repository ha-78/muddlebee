<?php

namespace App\Http\Requests;

use App\Models\ItemList;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('list');

        return $list instanceof ItemList && $this->user()->can('archive', $list);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
