@props(['meal', 'date' => null, 'variant' => 'tonal', 'compact' => false])

{{-- Opens the "pick-day" sheet for a meal, with its day (or today) filled in. --}}
<button
    type="button"
    x-data
    x-on:click="$dispatch('plan-meal', @js(['id' => $meal->id, 'name' => $meal->name, 'date' => $date ?? today()->toDateString()])); $dispatch('open-sheet', 'pick-day')"
    aria-haspopup="dialog"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-1.5 rounded-field font-semibold',
        'h-9 px-3 text-[14px]' => $compact,
        'h-11 px-4 text-[15px]' => ! $compact,
        'bg-primary text-primary-ink' => $variant === 'primary',
        'bg-tonal text-tonal-ink' => $variant === 'tonal',
    ]) }}
>
    {{ $slot }}
</button>
