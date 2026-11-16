{{-- A planned meal in the planner, with "Ändra" (author or admin), "Flytta…" and "Ta bort från dagen". On a planner day ($sortable)
     it can also be dragged to another day or back to the shortlist. --}}
<x-meal
    :meal="$meal"
    :handle="$canPlan && ($sortable ?? false)"
    data-meal-id="{{ $meal->id }}"
    data-day="{{ $meal->calendarDate->datetime }}"
    data-plan-url="{{ route('meals.plan', $meal) }}"
    data-unplan-url="{{ route('meals.unplan', $meal) }}"
    x-sort:item="{{ $meal->id }}"
>
    @if ($showDay ?? false)
        <x-slot:meta>{{ $meal->calendarDate->heading }}</x-slot:meta>
    @endif

    @if ($canPlan || auth()->user()->can('update', $meal))
        <x-slot:actions>
            @can('update', $meal)
                <x-meals.edit-button :meal="$meal" />
            @endcan

            @if ($canPlan)
                <x-meals.pick-day-button :meal="$meal" :date="$meal->calendarDate->datetime">{{ __('meals.move') }}</x-meals.pick-day-button>

                <form method="POST" action="{{ route('meals.unplan', $meal) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">{{ __('meals.unplan') }}</x-ui.button>
                </form>
            @endif
        </x-slot:actions>
    @endif
</x-meal>
