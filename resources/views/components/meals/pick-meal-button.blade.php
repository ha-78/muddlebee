@props(['day'])

{{-- "+ Planera" on a planner day: opens the "pick-meal" sheet for that day. --}}
<button
    type="button"
    x-data
    x-on:click="$dispatch('plan-date', @js(['date' => $day->datetime, 'heading' => $day->heading])); $dispatch('open-sheet', 'pick-meal')"
    aria-haspopup="dialog"
    aria-label="{{ __('meals.plan_for', ['day' => $day->heading]) }}"
    {{ $attributes->class('inline-flex items-center gap-1 font-semibold text-accent') }}
>
    <x-icon name="plus" size="4" stroke="2" />{{ __('meals.plan_short') }}
</button>
