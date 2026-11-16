<?php

namespace App\Http\Requests;

use App\Models\Notice;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Notice::class);
    }

    protected function prepareForValidation(): void
    {
        $time = trim((string) $this->input('time'));

        $this->merge([
            'body' => trim((string) $this->input('body')),
            'time' => $time === '' ? null : $time,
            'private' => $this->boolean('private'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'body' => ['required', 'string', 'max:255'],
            'time' => ['nullable', 'date_format:H:i'],
            'private' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => __('notices.date'),
            'body' => __('notices.body'),
            'time' => __('notices.time'),
        ];
    }

    public function day(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->validated('date'), config('app.timezone'))->startOfDay();
    }

    public function isPublic(): bool
    {
        return ! $this->validated('private');
    }
}
