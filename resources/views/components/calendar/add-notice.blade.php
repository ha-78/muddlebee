@props(['date', 'cover' => false])

{{-- The "+" on a day: opens the "Ny notis" sheet with this day filled in. With "cover" (small boxes)
     the button fills its positioned parent, so the whole box is the hit area, and a small + sits in
     the bottom-right corner. Content that should stay tappable on its own goes above it (relative z-10). --}}
@can('create', App\Models\Notice::class)
    <button
        type="button"
        x-data
        x-on:click="$dispatch('notice-date', '{{ $date->datetime }}'); $dispatch('open-sheet', 'new-notice')"
        aria-label="{{ __('notices.new_for', ['day' => $date->weekday.' '.$date->day.' '.$date->month]) }}"
        @if ($cover)
            {{ $attributes->class('absolute inset-0 rounded-[inherit] text-tonal-ink') }}
        @else
            {{ $attributes->class('grid shrink-0 place-items-center rounded-full bg-surface text-tonal-ink') }}
        @endif
    >
        @if ($cover)
            <span class="absolute right-2 bottom-2 grid size-6 place-items-center rounded-full bg-surface shadow-sm">
                <x-icon name="plus" size="3.5" stroke="2.4" />
            </span>
        @else
            <x-icon name="plus" size="4" stroke="2.2" />
        @endif
    </button>
@endcan
