@props(['date'])

{{-- The "+" on a day: opens the "Ny notis" sheet with this day filled in. --}}
@can('create', App\Models\Notice::class)
    <button
        type="button"
        x-data
        x-on:click="$dispatch('notice-date', '{{ $date->datetime }}'); $dispatch('open-sheet', 'new-notice')"
        aria-label="{{ __('notices.new_for', ['day' => $date->weekday.' '.$date->day.' '.$date->month]) }}"
        {{ $attributes->class('grid shrink-0 place-items-center rounded-full bg-surface text-tonal-ink') }}
    >
        <x-icon name="plus" size="4" stroke="2.2" />
    </button>
@endcan
