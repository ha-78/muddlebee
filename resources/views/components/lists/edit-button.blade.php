@props(['event', 'editor', 'label'])

{{-- A pencil that opens an edit sheet: sends the editor data with the window event, then opens the sheet of the same name. --}}
<button
    type="button"
    x-data
    x-on:click="$dispatch(@js($event), @js($editor)); $dispatch('open-sheet', @js($event))"
    aria-haspopup="dialog"
    aria-label="{{ $label }}"
    {{ $attributes->class('grid size-11 shrink-0 place-items-center rounded-field text-muted') }}
>
    <x-icon name="pencil" size="5" />
</button>
