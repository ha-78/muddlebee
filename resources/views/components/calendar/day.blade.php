@props(['date', 'label' => null, 'big' => false])

{{-- One day on the start page, in the calendar's burgundy. A big box (today, tomorrow) has the date
     beside the day's notices and meals, with the + next to the notices; in a small box the whole box
     adds a notice, except the notices and meals on it, with a small + in the bottom-right corner. --}}
<article
    data-color="{{ \App\Enums\Section::Calendar->color() }}"
    {{ $attributes->class([
        'rounded-[20px] bg-tonal text-tonal-ink',
        'flex gap-3.5 p-4' => $big,
        'relative flex min-w-0 flex-col gap-1.5 px-2.5 pt-2.5 pb-9' => ! $big,
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
        <time datetime="{{ $date->datetime }}" class="flex min-w-0 items-baseline-last gap-2 px-0.5">
            <span class="text-[34px] leading-none font-bold tabular-nums">{{ $date->day }}</span>
            <span class="flex min-w-0 flex-col text-[12px] leading-tight">
                <span class="truncate font-bold uppercase tracking-wide">{{ $date->weekday }}</span>
                <span class="truncate">{{ $date->month }}</span>
            </span>
        </time>

        {{-- The whole box adds a notice (a small + in the corner shows it); notices and meals sit above it.
             The bottom padding keeps them clear of the +. --}}
        <x-calendar.add-notice :date="$date" cover />
    @endif

    <div @class(['flex min-w-0 flex-1 flex-col', 'gap-2' => $big, 'gap-1.5' => ! $big])>
        <div class="flex items-start gap-2">
            <ul class="flex min-w-0 flex-1 flex-col">
                @foreach ($date->loaded('notices') as $notice)
                    <li @class(['relative z-10' => ! $big])>
                        <x-calendar.notice :notice="$notice" :big="$big" />
                    </li>
                @endforeach
            </ul>

            @if ($big)
                <x-calendar.add-notice :date="$date" class="size-9" />
            @endif
        </div>

        @if ($date->loaded('meals')->isNotEmpty())
            <div data-color="{{ \App\Enums\Section::Meals->color() }}" @class(['pointer-events-none relative z-10 *:pointer-events-auto' => ! $big, 'mt-auto flex flex-wrap', 'gap-1.5' => $big, 'gap-1' => ! $big])>
                @foreach ($date->loaded('meals') as $meal)
                    <span @class(['inline-flex max-w-full items-center gap-1.5 rounded-field bg-tonal font-semibold text-tonal-ink', 'px-2.5 py-1.5 text-[14px]' => $big, 'px-2 py-1 text-[12px]' => ! $big])>
                        <x-icon name="utensils" size="4" class="shrink-0" />
                        <span class="truncate">{{ $meal->name }}</span>
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</article>
