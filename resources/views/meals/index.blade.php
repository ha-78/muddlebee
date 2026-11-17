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

        @if (auth()->user()->canWrite())
            <x-meals.edit-sheet />
        @endif

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
                    :value="old('form') === 'edit-meal' ? '' : old('name')"
                    :show-error="old('form') !== 'edit-meal'"
                    required
                    maxlength="255"
                    expandable
                    :more-label="__('meals.more')"
                    :expanded="old('form') !== 'edit-meal' && ($errors->hasAny(['recipe_link', 'comment']) || filled(old('recipe_link')) || filled(old('comment')))"
                >
                    <x-ui.field :label="__('meals.recipe_link')" name="recipe_link" type="url" :value="old('form') === 'edit-meal' ? '' : old('recipe_link')" maxlength="2048" placeholder="https://" :show-error="old('form') !== 'edit-meal'" />
                    <x-ui.field :label="__('meals.comment')" name="comment" multiline maxlength="10000" :show-error="old('form') !== 'edit-meal'">{{ old('form') === 'edit-meal' ? '' : old('comment') }}</x-ui.field>
                </x-ui.quick-add>
            @endcan
        @endif

        @if ($status === App\Enums\MealStatus::Planned)
            @include('meals.planner')
        @elseif ($meals->isEmpty())
            <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ $status->emptyMessage() }}</p>
        @else
            @if ($status === App\Enums\MealStatus::Wishlist)
                {{-- Nyast / Högst betyg --}}
                <div class="flex items-center justify-end gap-1 text-[13px]" role="group" aria-label="{{ __('meals.sort') }}">
                    @foreach ([App\Services\MealService::SORT_NEWEST => __('meals.sort_newest'), App\Services\MealService::SORT_RATING => __('meals.sort_rating')] as $value => $label)
                        <a
                            href="{{ route('meals.index', ['status' => 'wishlist', 'sort' => $value]) }}"
                            @if ($sort === $value) aria-current="true" @endif
                            @class([
                                'inline-flex h-8 items-center rounded-full px-3',
                                'bg-tonal font-semibold text-tonal-ink' => $sort === $value,
                                'font-medium text-muted' => $sort !== $value,
                            ])
                        >{{ $label }}</a>
                    @endforeach
                </div>
            @endif

            <x-ui.group>
                @foreach ($meals as $meal)
                    @if ($status === App\Enums\MealStatus::Wishlist)
                        <x-meals.rating-scope :meal="$meal">
                            @include('meals.row', ['meal' => $meal, 'rated' => true])
                        </x-meals.rating-scope>
                    @else
                        @include('meals.row', ['meal' => $meal, 'rated' => false])
                    @endif
                @endforeach
            </x-ui.group>
        @endif
    </div>
@endsection
