@props([
    'text',
    'label',
])

{{-- A small icon button that copies $text to the clipboard (copyText in resources/js/copy-text.js).
     The icon turns into a check mark for a moment, and a live region says "Kopierad" for screen readers. --}}
<span x-data="copyText" class="contents">
    <x-ui.button
        variant="ghost"
        icon
        :aria-label="$label"
        :title="$label"
        data-copy="{{ $text }}"
        x-on:click="copy($el.dataset.copy)"
        {{ $attributes }}
    >
        <span class="text-muted" x-show="! copied"><x-icon name="copy" size="5" /></span>
        <span class="text-accent" x-show="copied" x-cloak><x-icon name="check" size="5" stroke="2" /></span>
    </x-ui.button>
    <span class="sr-only" aria-live="polite" x-text="copied ? @js(__('layout.copied')) : ''"></span>
</span>
