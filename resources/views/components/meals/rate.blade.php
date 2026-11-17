@props(['meal'])

{{-- "Ditt betyg": five stars to pick from (a radio group, so arrow keys and screen readers work), and
     "Ta bort mitt betyg" once you have rated. Saved straight away by the surrounding mealRating. --}}
@php($own = $meal->myRating())

<fieldset {{ $attributes->class('space-y-1.5') }} x-bind:disabled="pending">
    <legend class="text-[13px] font-medium text-muted">{{ __('meals.my_rating') }}</legend>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <div class="flex items-center">
            @for ($i = 1; $i <= 5; $i++)
                <label class="grid size-10 cursor-pointer place-items-center rounded-full has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-ink">
                    <input
                        type="radio"
                        name="rating-{{ $meal->id }}"
                        value="{{ $i }}"
                        class="sr-only"
                        @checked($own === $i)
                        x-bind:checked="rating === {{ $i }}"
                        x-on:change="set({{ $i }})"
                    >
                    <span class="sr-only">{{ __('meals.rate_stars', ['count' => $i]) }}</span>
                    <x-meals.star
                        :class="$own !== null && $i <= $own ? 'size-7 text-accent' : 'size-7 text-line'"
                        :x-bind:class="'{ \'text-accent\': rating !== null && '.$i.' <= rating, \'text-line\': rating === null || '.$i.' > rating }'"
                    />
                </label>
            @endfor
        </div>

        <button
            type="button"
            class="text-[14px] font-semibold text-accent underline-offset-2 hover:underline"
            x-show="rating !== null"
            x-on:click="clear()"
            @if ($own === null) x-cloak @endif
        >{{ __('meals.remove_rating') }}</button>
    </div>

    <p x-show="error" x-text="error" x-cloak class="flex items-center gap-1.5 text-[13px] font-semibold text-field-error-ink"></p>
</fieldset>
