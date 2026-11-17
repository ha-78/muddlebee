{{-- One meal on a meals tab. On the wishlist ($rated) it shows the average rating and, for those who may rate, the stars to pick. --}}
<x-meal :meal="$meal">
    @if ($rated)
        <x-slot:rating><x-meals.rating-summary :meal="$meal" /></x-slot:rating>

        @can('rate', $meal)
            <x-slot:rate><x-meals.rate :meal="$meal" /></x-slot:rate>
        @endcan
    @endif

    @if (auth()->user()->can('update', $meal) || auth()->user()->can('move', $meal) || ($meal->status === App\Enums\MealStatus::Shortlist && auth()->user()->can('plan', $meal)))
        <x-slot:actions>
            @can('update', $meal)
                <x-meals.edit-button :meal="$meal" />
            @endcan

            @if ($meal->status === App\Enums\MealStatus::Shortlist && auth()->user()->can('plan', $meal))
                <x-meals.pick-day-button :meal="$meal" variant="primary">{{ __('meals.plan') }}</x-meals.pick-day-button>
            @endif

            @can('move', $meal)
                @foreach ($meal->status->moveTargets() as $target)
                    <form method="POST" action="{{ route('meals.status', $meal) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $target->value }}">
                        <x-ui.button
                            type="submit"
                            :variant="$target === App\Enums\MealStatus::Shortlist ? 'primary' : ($target === App\Enums\MealStatus::Discarded ? 'danger' : 'tonal')"
                        >{{ $meal->status->moveLabel($target) }}</x-ui.button>
                    </form>
                @endforeach
            @endcan
        </x-slot:actions>
    @endif
</x-meal>
