@props(['meal'])

{{-- Holds one wishlist meal's ratings for the mealRating Alpine component: the row's average and count
     and the user's own rating, with the texts the script needs. Wrap the whole meal row in it. --}}
<div
    x-data="mealRating"
    data-url="{{ route('meals.rating', $meal) }}"
    data-rating="{{ $meal->myRating() }}"
    data-average="{{ $meal->isUnrated() ? '' : round((float) $meal->ratings_avg_rating, 2) }}"
    data-stars="{{ $meal->averageStars() }}"
    data-count="{{ (int) $meal->ratings_count }}"
    data-label-none="{{ __('meals.rating_none') }}"
    data-label-one="{{ trans_choice('meals.rating_count', 1, ['count' => ':count']) }}"
    data-label-many="{{ trans_choice('meals.rating_count', 2, ['count' => ':count']) }}"
    data-label-average="{{ __('meals.rating_average', ['average' => ':average']) }}"
    data-failed="{{ __('meals.rating_failed') }}"
    {{ $attributes }}
>
    {{ $slot }}
</div>
