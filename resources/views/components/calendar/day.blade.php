@props(['date', 'label' => null, 'big' => false])

{{-- One day on the start page, in the calendar's burgundy. A big box (today, tomorrow) has the date
     beside the day's notices and meals; a small box stacks them. Notices and meals show once they
     are loaded on the day (later steps); until then the box shows its empty state. --}}
<article
    data-color="{{ \App\Enums\Section::Calendar->color() }}"
    {{ $attributes->class([
        'rounded-[20px] bg-tonal text-tonal-ink',
        'flex gap-3.5 p-4' => $big,
        'flex min-w-0 flex-col gap-1.5 p-2.5' => ! $big,
    ]) }}
>
    @if ($big)
        <div class="flex w-24 shrink-0 flex-col items-center justify-center rounded-card bg-surface px-1 py-2 text-center">
            <span class="text-[12px] font-bold uppercase tracking-wide">{{ $label }}</span>
            <span class="text-[13px]">{{ $date->weekday }}</span>
            <time datetime="{{ $date->datetime }}" class="flex flex-col items-center">
                <span class="my-1 text-[56px] leading-none font-bold tabular-nums">{{ $date->day }}</span>
                <span class="text-[13px]">{{ $date->month }}</span>
            </time>
        </div>
    @else
        <div class="flex items-start justify-between gap-1">
            <time datetime="{{ $date->datetime }}" class="flex min-w-0 items-baseline-last gap-2 px-0.5">
                <span class="text-[34px] leading-none font-bold tabular-nums">{{ $date->day }}</span>
                <span class="flex min-w-0 flex-col text-[12px] leading-tight">
                    <span class="truncate font-bold uppercase tracking-wide">{{ $date->weekday }}</span>
                    <span class="truncate">{{ $date->month }}</span>
                </span>
            </time>

            <x-calendar.add-notice :date="$date" class="size-8" />
        </div>
    @endif

    <div @class(['flex min-w-0 flex-1 flex-col', 'gap-2' => $big, 'gap-1.5' => ! $big])>
        @if ($big)
            <div class="flex min-h-8 items-center justify-between gap-2">
                <h3 class="text-[12px] font-bold uppercase tracking-wide">{{ __('calendar.notices') }}</h3>
                <x-calendar.add-notice :date="$date" class="size-9" />
            </div>
        @endif

        <ul @class(['flex flex-col', 'gap-1' => $big, 'gap-0.5' => ! $big])>
            @forelse ($date->loaded('notices') as $notice)
                <li>
                    <x-calendar.notice :notice="$notice" :big="$big" />
                </li>
            @empty
                @if ($big)
                    <li class="px-0.5 text-[14px]">{{ __('calendar.no_notices') }}</li>
                @endif
            @endforelse
        </ul>

        @if ($date->loaded('meals')->isNotEmpty() || auth()->user()?->can('plan', App\Models\Meal::class))
            <div data-color="{{ \App\Enums\Section::Meals->color() }}" @class(['mt-auto flex flex-wrap', 'gap-1.5' => $big, 'gap-1' => ! $big])>
                @foreach ($date->loaded('meals') as $meal)
                    <span @class(['inline-flex max-w-full items-center gap-1.5 rounded-field bg-tonal font-semibold text-tonal-ink', 'px-2.5 py-1.5 text-[14px]' => $big, 'px-2 py-1 text-[12px]' => ! $big])>
                        <x-icon name="utensils" size="4" class="shrink-0" />
                        <span class="truncate">{{ $meal->name }}</span>
                    </span>
                @endforeach

                @can('plan', App\Models\Meal::class)
                    <a
                        href="{{ route('meals.index', ['status' => 'planned', 'date' => $date->datetime]) }}"
                        @class(['inline-flex items-center gap-1 rounded-field border-[1.5px] border-dashed border-accent font-semibold text-tonal-ink', 'px-2.5 py-1.5 text-[14px]' => $big, 'px-2 py-1 text-[12px]' => ! $big])
                    >
                        <x-icon name="plus" size="4" stroke="2" class="shrink-0" />
                        {{ $big ? __('calendar.plan_meal') : __('calendar.plan_short') }}
                    </a>
                @endcan
            </div>
        @endif
    </div>
</article>
