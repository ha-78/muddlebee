@extends('layouts.app')

@section('content')
    <div class="space-y-4">
        @error('meal')
            <x-ui.message type="error">{{ $message }}</x-ui.message>
        @enderror

        <x-ui.segmented
            :label="__('meals.meals')"
            :options="collect(App\Enums\MealStatus::tabs())->map(fn ($tab) => [
                'label' => $tab->tabLabel(),
                'href' => route('meals.index', ['status' => $tab->value]),
                'selected' => $tab === $status,
            ])->all()"
        />

        @if ($status === App\Enums\MealStatus::Shortlist)
            @can('plan', App\Models\Meal::class)
                <x-meals.plan-sheets />
            @endcan
        @endif

        @if ($status === App\Enums\MealStatus::Wishlist)
            @can('create', App\Models\Meal::class)
                <x-ui.quick-add
                    :label="__('meals.new_wish')"
                    :placeholder="__('meals.prompt')"
                    name="name"
                    :action="route('meals.store')"
                    :value="old('name')"
                    required
                    maxlength="255"
                    expandable
                    :more-label="__('meals.more')"
                    :expanded="$errors->hasAny(['recipe_link', 'comment']) || filled(old('recipe_link')) || filled(old('comment'))"
                >
                    <x-ui.field :label="__('meals.recipe_link')" name="recipe_link" type="url" :value="old('recipe_link')" maxlength="2048" placeholder="https://" />
                    <x-ui.field :label="__('meals.comment')" name="comment" multiline maxlength="10000">{{ old('comment') }}</x-ui.field>
                </x-ui.quick-add>
            @endcan
        @endif

        @if ($status === App\Enums\MealStatus::Planned)
            @include('meals.planner')
        @elseif ($meals->isEmpty())
            <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ $status->emptyMessage() }}</p>
        @else
            <x-ui.group>
                @foreach ($meals as $meal)
                    <x-meal :meal="$meal">
                        @if (auth()->user()->can('move', $meal) || ($meal->status === App\Enums\MealStatus::Shortlist && auth()->user()->can('plan', $meal)))
                            <x-slot:actions>
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
                @endforeach
            </x-ui.group>
        @endif
    </div>
@endsection
