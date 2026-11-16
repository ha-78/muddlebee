@props(['defaultDate'])

{{-- The "Ny notis" and "Ändra notis" sheets. A day opens them with window events: "notice-date"
     (a Y-m-d) before "new-notice", or "edit-notice" (Notice::toEditor()) before opening "edit-notice".
     After a failed save, the sheet that was used opens again with what was typed. --}}
@php
    $failed = $errors->any() ? old('form') : null;
    $edit = $failed === 'edit-notice' && old('notice_id')
        ? [
            'id' => (int) old('notice_id'),
            'action' => route('notices.update', old('notice_id')),
            'destroy' => route('notices.destroy', old('notice_id')),
            'date' => old('date'),
            'body' => old('body'),
            'time' => old('time'),
            'private' => (bool) old('private'),
        ]
        : ['id' => null, 'action' => '', 'destroy' => '', 'date' => '', 'body' => '', 'time' => '', 'private' => false];
@endphp

<div {{ $attributes }}>
    @can('create', App\Models\Notice::class)
        <x-ui.sheet name="new-notice" :title="__('notices.new')" :close="__('layout.close')" :open="$failed === 'new-notice'">
            <form
                method="POST"
                action="{{ route('notices.store') }}"
                class="space-y-3"
                x-data="{ date: @js($failed === 'new-notice' ? old('date') : $defaultDate) }"
                x-on:notice-date.window="date = $event.detail"
            >
                @csrf
                <input type="hidden" name="form" value="new-notice">

                <x-ui.field :label="__('notices.date')" name="date" id="new-notice-date" type="date" x-model="date" :min="$defaultDate" required :show-error="$failed === 'new-notice'" />
                <x-ui.field :label="__('notices.body')" name="body" id="new-notice-body" :value="$failed === 'new-notice' ? old('body') : ''" required maxlength="255" :show-error="$failed === 'new-notice'" data-autofocus />
                <x-ui.field :label="__('notices.time').' ('.__('notices.time_hint').')'" name="time" id="new-notice-time" type="time" :value="$failed === 'new-notice' ? old('time') : ''" :show-error="$failed === 'new-notice'" />
                <x-ui.switch :label="__('notices.private')" name="private" icon="lock" :checked="$failed === 'new-notice' && old('private')" />

                <div class="flex gap-2 pt-1">
                    <x-ui.button variant="tonal" class="flex-1" x-on:click="open = false">{{ __('notices.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" class="flex-2">{{ __('notices.save') }}</x-ui.button>
                </div>
            </form>
        </x-ui.sheet>
    @endcan

    <x-ui.sheet name="edit-notice" :title="__('notices.edit')" :close="__('layout.close')" :open="$failed === 'edit-notice'">
        <div x-data="{ notice: @js($edit) }" x-on:edit-notice.window="notice = $event.detail" class="space-y-3">
            <form method="POST" action="{{ $edit['action'] }}" x-bind:action="notice.action" class="space-y-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="form" value="edit-notice">
                <input type="hidden" name="notice_id" value="{{ $edit['id'] }}" x-bind:value="notice.id">

                <x-ui.field :label="__('notices.date')" name="date" id="edit-notice-date" type="date" x-model="notice.date" :min="$defaultDate" required :show-error="$failed === 'edit-notice'" />
                <x-ui.field :label="__('notices.body')" name="body" id="edit-notice-body" x-model="notice.body" required maxlength="255" :show-error="$failed === 'edit-notice'" />
                <x-ui.field :label="__('notices.time').' ('.__('notices.time_hint').')'" name="time" id="edit-notice-time" type="time" x-model="notice.time" :show-error="$failed === 'edit-notice'" />
                <x-ui.switch :label="__('notices.private')" name="private" icon="lock" model="notice.private" :checked="$edit['private']" />

                <div class="flex gap-2 pt-1">
                    <x-ui.button variant="tonal" class="flex-1" x-on:click="open = false">{{ __('notices.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" class="flex-2">{{ __('notices.save') }}</x-ui.button>
                </div>
            </form>

            <form method="POST" action="{{ $edit['destroy'] }}" x-bind:action="notice.destroy" onsubmit="return confirm(@js(__('notices.confirm_delete')))">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" class="w-full"><x-icon name="trash" size="5" />{{ __('notices.delete') }}</x-ui.button>
            </form>
        </div>
    </x-ui.sheet>
</div>
