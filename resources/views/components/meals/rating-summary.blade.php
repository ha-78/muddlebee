@props(['meal'])

{{-- The row's rating: average stars and "· 3 betyg", or pale stars and "Inga betyg ännu".
     The count is bold when you have rated the meal yourself.
     Server-rendered, then kept up to date by the surrounding mealRating. --}}
@php
    $count = (int) $meal->ratings_count;
    $countLabel = $meal->isUnrated() ? __('meals.rating_none') : trans_choice('meals.rating_count', $count, ['count' => $count]);
    $srLabel = $meal->isUnrated()
        ? $countLabel
        : __('meals.rating_average', ['average' => Illuminate\Support\Number::format((float) $meal->ratings_avg_rating, maxPrecision: 1, locale: app()->getLocale())]).', '.$countLabel;
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5') }}>
    <x-meals.stars :value="$meal->averageStars()" :muted="$meal->isUnrated()" live />
    <span aria-hidden="true" x-text="countLabel" x-bind:class="{ 'font-semibold': rating !== null }" @class(['font-semibold' => $meal->myRating() !== null])>{{ $countLabel }}</span>
    <span class="sr-only" x-text="srLabel">{{ $srLabel }}</span>
</span>
