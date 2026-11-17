{{-- The "Ändra lista" sheet, one per page. A list's pencil sends ItemList::toEditor() with the window event
     "edit-list" and opens the sheet. After a failed save it opens again with what was typed. --}}
@php
    $failed = $errors->any() && old('form') === 'edit-list' && old('list_edit_id');
    $list = $failed
        ? [
            'id' => (int) old('list_edit_id'),
            'action' => route('lists.update', old('list_edit_id')),
            'name' => (string) old('list_name'),
            'is_public' => (bool) old('list_is_public'),
        ]
        : ['id' => null, 'action' => '', 'name' => '', 'is_public' => false];
    $canPublish = auth()->user()->can('publish', App\Models\ItemList::class);
@endphp

<x-ui.sheet name="edit-list" :title="__('lists.edit_list')" :close="__('layout.close')" :open="$failed">
    <form
        method="POST"
        action="{{ $list['action'] }}"
        x-data="{ list: @js($list) }"
        x-on:edit-list.window="list = $event.detail"
        x-bind:action="list.action"
        class="space-y-3"
    >
        @csrf
        @method('PATCH')
        <input type="hidden" name="form" value="edit-list">
        <input type="hidden" name="list_edit_id" value="{{ $list['id'] }}" x-bind:value="list.id">

        <x-ui.field :label="__('lists.name')" name="list_name" id="edit-list-name" x-model="list.name" required maxlength="255" :show-error="$failed" data-autofocus />

        @if ($canPublish)
            <x-ui.switch :label="__('lists.is_public')" name="list_is_public" icon="globe" model="list.is_public" :checked="$list['is_public']" />
        @else
            {{-- Without the right to share lists the setting stays as it is. --}}
            <input type="hidden" name="list_is_public" value="{{ $list['is_public'] ? 1 : 0 }}" x-bind:value="list.is_public ? 1 : 0">
        @endif

        <div class="flex gap-2 pt-1">
            <x-ui.button variant="tonal" class="flex-1" x-on:click="open = false">{{ __('lists.cancel') }}</x-ui.button>
            <x-ui.button type="submit" class="flex-2">{{ __('lists.save') }}</x-ui.button>
        </div>
    </form>
</x-ui.sheet>
