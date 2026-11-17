@props(['value' => 3.0, 'muted' => false, 'live' => false])

{{-- Five stars showing a value from 0 to 5 in half steps: filled in the accent colour, empty in the line colour.
     "muted" makes them pale (a meal nobody has rated). "live" follows the surrounding mealRating's
     stars and unrated (see resources/js/meal-rating.js) instead of the server's value. Decorative:
     the text next to them carries the meaning. --}}
<span
    aria-hidden="true"
    @if ($live) x-bind:class="{ 'opacity-45': unrated }" @endif
    {{ $attributes->class(['inline-flex shrink-0 items-center gap-px', 'opacity-45' => $muted]) }}
>
    @for ($i = 1; $i <= 5; $i++)
        @php
            $full = $value >= $i;
            $half = ! $full && $value >= $i - 0.5;
            $fill = Illuminate\Support\Arr::toCssClasses(['absolute inset-0 size-full text-accent', 'hidden' => ! $full && ! $half, '[clip-path:inset(0_50%_0_0)]' => $half]);
            $liveFill = '{ \'hidden\': stars < '.($i - 0.5).', \'[clip-path:inset(0_50%_0_0)]\': stars >= '.($i - 0.5).' && stars < '.$i.' }';
        @endphp
        <span class="relative inline-block size-4">
            <x-meals.star class="absolute inset-0 size-full text-line" />
            @if ($live)
                <x-meals.star :class="$fill" :x-bind:class="$liveFill" />
            @else
                <x-meals.star :class="$fill" />
            @endif
        </span>
    @endfor
</span>
