{{-- The "Ändra måltid" sheet, one per page. A meal's "Ändra" button sends its Meal::toEditor() with
     the window event "edit-meal" and opens the sheet. After a failed save it opens again with what was typed. --}}
@php
    $failed = $errors->any() && old('form') === 'edit-meal' && old('meal_id');
    $meal = $failed
        ? [
            'id' => (int) old('meal_id'),
            'action' => route('meals.update', old('meal_id')),
            'name' => (string) old('name'),
            'recipe_link' => (string) old('recipe_link'),
            'comment' => (string) old('comment'),
        ]
        : ['id' => null, 'action' => '', 'name' => '', 'recipe_link' => '', 'comment' => ''];
@endphp

<x-ui.sheet name="edit-meal" :title="__('meals.edit_meal')" :close="__('layout.close')" :open="$failed">
    <form
        method="POST"
        action="{{ $meal['action'] }}"
        x-data="{ meal: @js($meal) }"
        x-on:edit-meal.window="meal = $event.detail"
        x-bind:action="meal.action"
        class="space-y-3"
    >
        @csrf
        @method('PATCH')
        <input type="hidden" name="form" value="edit-meal">
        <input type="hidden" name="meal_id" value="{{ $meal['id'] }}" x-bind:value="meal.id">

        <x-ui.field :label="__('meals.name')" name="name" id="edit-meal-name" x-model="meal.name" required maxlength="255" :show-error="$failed" data-autofocus />
        <x-ui.field :label="__('meals.recipe_link')" name="recipe_link" id="edit-meal-recipe-link" type="url" x-model="meal.recipe_link" maxlength="2048" placeholder="https://" :show-error="$failed" />
        <x-ui.field :label="__('meals.comment')" name="comment" id="edit-meal-comment" multiline maxlength="10000" x-model="meal.comment" :show-error="$failed" />

        <div class="flex gap-2 pt-1">
            <x-ui.button variant="tonal" class="flex-1" x-on:click="open = false">{{ __('meals.cancel') }}</x-ui.button>
            <x-ui.button type="submit" class="flex-2">{{ __('meals.save') }}</x-ui.button>
        </div>
    </form>
</x-ui.sheet>
