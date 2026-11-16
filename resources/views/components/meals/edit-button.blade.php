@props(['meal'])

{{-- "Ändra" on a meal row: opens the "Ändra måltid" sheet with this meal filled in. --}}
<button
    type="button"
    x-data
    x-on:click="$dispatch('edit-meal', @js($meal->toEditor())); $dispatch('open-sheet', 'edit-meal')"
    aria-haspopup="dialog"
    {{ $attributes->class('inline-flex h-11 items-center justify-center gap-1.5 rounded-field bg-tonal px-4 text-[15px] font-semibold text-tonal-ink') }}
>
    {{ __('meals.edit') }}
</button>
