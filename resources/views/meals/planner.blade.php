{{-- The "Planerat" tab: the next days with their meals, the shortlist beside them on wide screens,
     meals planned further ahead and the ones planned before. Planners can drag meals by their grip
     between days and the shortlist (mealPlanner in app.js); every drag has a tap path too. --}}
@php
    $canPlan = auth()->user()->can('plan', App\Models\Meal::class);
    $canWish = auth()->user()->can('create', App\Models\Meal::class);
@endphp

@if ($canPlan)
    <x-meals.plan-sheets :shortlist="$shortlist" />
@endif

<div
    class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start"
    x-data="mealPlanner"
    data-highlight="{{ $highlight }}"
    data-failed="{{ __('meals.drop_failed') }}"
>
    @if ($canPlan)
        <div x-show="error" x-cloak class="lg:col-span-2">
            <x-ui.message type="error" :dismissible="false"><span x-text="error"></span></x-ui.message>
        </div>
    @endif

    <div class="space-y-5">
        @foreach ($days as $day)
            <x-ui.group
                :heading="$day->heading"
                id="day-{{ $day->datetime }}"
                data-day="{{ $day->datetime }}"
                :class="Illuminate\Support\Arr::toCssClasses(['scroll-mt-24 rounded-card', 'ring-2 ring-accent ring-offset-4 ring-offset-canvas' => $highlight === $day->datetime])"
            >
                @if ($day->date->isToday())
                    <x-slot:badge><x-ui.badge>{{ __('calendar.today') }}</x-ui.badge></x-slot:badge>
                @elseif ($day->date->isTomorrow())
                    <x-slot:badge><x-ui.badge>{{ __('calendar.tomorrow') }}</x-ui.badge></x-slot:badge>
                @endif

                @if ($canPlan)
                    <x-slot:actions>
                        <x-meals.pick-meal-button :day="$day" />
                    </x-slot:actions>
                @endif

                <div
                    data-zone="{{ $day->datetime }}"
                    class="min-h-12 divide-y divide-surface-line"
                    @if ($canPlan) x-sort="drop($item, @js($day->datetime))" x-sort:group="meals" x-sort:config="{ sort: false }" @endif
                >
                    @foreach ($day->loaded('meals') as $meal)
                        @include('meals.planned-meal', ['meal' => $meal, 'canPlan' => $canPlan, 'sortable' => true])
                    @endforeach

                    <p data-empty @class(['px-4 py-3 text-[14px] text-muted', 'hidden' => $day->loaded('meals')->isNotEmpty()])>{{ __('meals.nothing_planned') }}</p>
                </div>
            </x-ui.group>
        @endforeach

        @if ($later->isNotEmpty())
            <x-ui.group :heading="__('meals.later')">
                @foreach ($later as $meal)
                    @include('meals.planned-meal', ['meal' => $meal, 'canPlan' => $canPlan, 'showDay' => true])
                @endforeach
            </x-ui.group>
        @endif

        @if ($earlier->isNotEmpty())
            <x-ui.group :heading="__('meals.earlier')">
                @foreach ($earlier as $meal)
                    <x-meal :meal="$meal">
                        <x-slot:meta>{{ $meal->calendarDate->heading }}</x-slot:meta>
                        @if ($canWish)
                            <x-slot:actions>
                                <form method="POST" action="{{ route('meals.again', $meal) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="tonal">{{ __('meals.wish_again') }}</x-ui.button>
                                </form>
                            </x-slot:actions>
                        @endif
                    </x-meal>
                @endforeach
            </x-ui.group>
        @endif
    </div>

    @if ($canPlan)
        <x-ui.group :heading="__('meals.tabs.shortlist')" class="hidden lg:sticky lg:top-4 lg:block">
            <x-slot:actions>{{ __('meals.drag_hint') }}</x-slot:actions>
            <div data-zone="" class="min-h-12 divide-y divide-surface-line" x-sort="drop($item, '')" x-sort:group="meals" x-sort:config="{ sort: false }">
                @foreach ($shortlist as $meal)
                    <div
                        class="flex min-h-12 items-center gap-1 bg-surface py-2 pr-4"
                        data-meal-id="{{ $meal->id }}"
                        data-day=""
                        data-plan-url="{{ route('meals.plan', $meal) }}"
                        x-sort:item="{{ $meal->id }}"
                    >
                        <span x-sort:handle class="grid w-9 shrink-0 cursor-grab touch-none place-items-center pl-1 text-muted active:cursor-grabbing" title="{{ __('meals.drag') }}">
                            <x-icon name="grip" size="5" stroke="3" />
                            <span class="sr-only">{{ __('meals.drag') }}</span>
                        </span>
                        <span class="min-w-0 flex-1 text-[15px] font-semibold break-words">{{ $meal->name }}</span>
                        <x-meals.pick-day-button :meal="$meal" compact>{{ __('meals.plan') }}</x-meals.pick-day-button>
                    </div>
                @endforeach

                <p data-empty @class(['px-4 py-3 text-[14px] text-muted', 'hidden' => $shortlist->isNotEmpty()])>{{ __('meals.shortlist_empty') }}</p>
            </div>
        </x-ui.group>
    @endif
</div>
