@props(['shortlist' => null])

{{-- The meal planner's sheets. A day opens "pick-meal" with the window event "plan-date"
     ({ date, heading }) and lists the shortlist; a meal opens "pick-day" with "plan-meal"
     ({ id, name, date }) and asks for a day. After a failed save, "pick-day" opens again. --}}
@php
    $failed = $errors->has('date') && old('form') === 'pick-day' ? (int) old('meal_id') : null;
    $retry = $failed
        ? ['id' => $failed, 'name' => (string) old('meal_name'), 'date' => (string) old('date')]
        : ['id' => null, 'name' => '', 'date' => today()->toDateString()];
    $planUrl = route('meals.plan', ['meal' => '__MEAL__']);
@endphp

<div {{ $attributes }}>
    @if ($shortlist !== null)
        <x-ui.sheet name="pick-meal" :title="__('meals.pick_meal')" :close="__('layout.close')">
            <div x-data="{ date: '', heading: '' }" x-on:plan-date.window="date = $event.detail.date; heading = $event.detail.heading" class="space-y-3">
                <p class="text-[14px] text-muted" x-text="heading"></p>

                @if ($shortlist->isEmpty())
                    <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ __('meals.shortlist_empty') }}</p>
                    <a href="{{ route('meals.index', ['status' => 'shortlist']) }}" class="inline-flex h-11 w-full items-center justify-center rounded-field bg-tonal px-4 text-[15px] font-semibold text-tonal-ink">{{ __('meals.to_shortlist') }}</a>
                @else
                    <div class="divide-y divide-surface-line overflow-hidden rounded-card border border-surface-line bg-surface">
                        @foreach ($shortlist as $meal)
                            <form method="POST" action="{{ route('meals.plan', $meal) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="date" x-bind:value="date">
                                <button type="submit" class="flex min-h-12 w-full items-center gap-3 px-4 py-2 text-left text-[15px] font-semibold text-ink">
                                    <span class="shrink-0 text-accent"><x-icon name="utensils" size="5" /></span>
                                    <span class="min-w-0 flex-1 break-words">{{ $meal->name }}</span>
                                    <x-icon name="plus" size="5" stroke="2" class="shrink-0 text-muted" />
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.sheet>
    @endif

    <x-ui.sheet name="pick-day" :title="__('meals.pick_day')" :close="__('layout.close')" :open="$failed !== null">
        <form
            method="POST"
            action="{{ $failed ? route('meals.plan', $failed) : '' }}"
            x-data="{ meal: @js($retry), url: @js($planUrl) }"
            x-on:plan-meal.window="meal = $event.detail"
            x-bind:action="meal.id ? url.replace('__MEAL__', meal.id) : ''"
            class="space-y-3"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="form" value="pick-day">
            <input type="hidden" name="meal_id" value="{{ $retry['id'] }}" x-bind:value="meal.id">
            <input type="hidden" name="meal_name" value="{{ $retry['name'] }}" x-bind:value="meal.name">

            <p class="text-[15px] font-semibold" x-text="meal.name">{{ $retry['name'] }}</p>

            <x-ui.field :label="__('meals.date')" name="date" id="pick-day-date" type="date" x-model="meal.date" :min="today()->toDateString()" required :show-error="$failed !== null" data-autofocus />

            <div class="flex gap-2 pt-1">
                <x-ui.button variant="tonal" class="flex-1" x-on:click="open = false">{{ __('notices.cancel') }}</x-ui.button>
                <x-ui.button type="submit" class="flex-2">{{ __('notices.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.sheet>
</div>
