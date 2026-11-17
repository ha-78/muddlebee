{{-- The "Ändra post" sheet, one per page. An item's pencil sends ListItem::toEditor() with the window event
     "edit-item" and opens the sheet. "Bara jag" only shows where it applies (the list's creator, in a shared list).
     After a failed save it opens again with what was typed. --}}
@php
    $failed = $errors->any() && old('form') === 'edit-item' && old('item_edit_id');
    $saved = $failed ? App\Models\ListItem::query()->find(old('item_edit_id')) : null;
    $item = $saved !== null
        ? array_merge($saved->toEditor(auth()->user()), [
            'name' => (string) old('edit_name'),
            'description' => (string) old('edit_description'),
            'estimate' => (string) old('edit_estimate'),
            'private' => (bool) old('edit_private'),
        ])
        : ['id' => null, 'action' => '', 'name' => '', 'description' => '', 'estimate' => '', 'private' => false, 'can_private' => false];
    $failed = $failed && $saved !== null;
@endphp

<x-ui.sheet name="edit-item" :title="__('lists.edit_item')" :close="__('layout.close')" :open="$failed">
    <form
        method="POST"
        action="{{ $item['action'] }}"
        x-data="{ item: @js($item) }"
        x-on:edit-item.window="item = $event.detail"
        x-bind:action="item.action"
        class="space-y-3"
    >
        @csrf
        @method('PATCH')
        <input type="hidden" name="form" value="edit-item">
        <input type="hidden" name="item_edit_id" value="{{ $item['id'] }}" x-bind:value="item.id">

        <x-ui.field :label="__('lists.name')" name="edit_name" id="edit-item-name" x-model="item.name" required maxlength="255" :show-error="$failed" data-autofocus />
        <x-ui.field :label="__('lists.description')" name="edit_description" id="edit-item-description" multiline maxlength="10000" x-model="item.description" :show-error="$failed" />
        <x-ui.field :label="__('lists.estimate')" name="edit_estimate" id="edit-item-estimate" type="number" step="1" inputmode="numeric" x-model="item.estimate" :show-error="$failed" />

        <fieldset x-show="item.can_private" x-bind:disabled="! item.can_private" @unless ($item['can_private']) x-cloak disabled @endunless>
            <x-ui.switch :label="__('lists.private')" name="edit_private" icon="lock" model="item.private" :checked="$item['private']" />
        </fieldset>

        <div class="flex gap-2 pt-1">
            <x-ui.button variant="tonal" class="flex-1" x-on:click="open = false">{{ __('lists.cancel') }}</x-ui.button>
            <x-ui.button type="submit" class="flex-2">{{ __('lists.save') }}</x-ui.button>
        </div>
    </form>
</x-ui.sheet>
