<?php

namespace App\Http\Requests;

use App\Rules\SingleEmoji;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    /**
     * Everyone who is logged in may change their own avatar.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['avatar_emoji' => trim((string) $this->input('avatar_emoji'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar_emoji' => ['required', 'string', new SingleEmoji],
        ];
    }
}
