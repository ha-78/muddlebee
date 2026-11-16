<?php

namespace App\Http\Requests;

use App\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;

class DeleteNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notice = $this->route('notice');

        return $notice instanceof Notice && $this->user()->can('delete', $notice);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
