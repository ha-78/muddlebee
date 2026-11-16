@props(['meal', 'handle' => false])

{{-- One meal in a list. Opens to show the recipe link and comment; the "actions" slot holds what the user may do with it,
     and an optional "meta" slot (such as the planned day) goes before the author. With "handle", a grip
     on the left drags the meal (Alpine sort). --}}
<div x-data="{ open: false }" {{ $attributes->class(['bg-surface']) }}>
    <div class="flex items-stretch">
    @if ($handle)
        <span
            x-sort:handle
            class="grid w-9 shrink-0 cursor-grab touch-none place-items-center pl-1 text-muted active:cursor-grabbing"
            title="{{ __('meals.drag') }}"
        >
            <x-icon name="grip" size="5" stroke="3" />
            <span class="sr-only">{{ __('meals.drag') }}</span>
        </span>
    @endif
    <button
        type="button"
        @class(['flex min-h-14 min-w-0 flex-1 items-center gap-3 py-2 pr-4 text-left text-ink', 'pl-4' => ! $handle, 'pl-1' => $handle])
        x-on:click="open = ! open"
        x-bind:aria-expanded="open.toString()"
        aria-controls="meal-{{ $meal->id }}-details"
    >
        <span class="shrink-0 text-accent"><x-icon name="utensils" size="5" /></span>
        <span class="min-w-0 flex-1">
            <span class="block text-[15px] font-semibold break-words">{{ $meal->name }}</span>
            <span class="block text-[13px] text-muted">
                @isset($meta)
                    {{ $meta }}
                    <span aria-hidden="true"> · </span>
                @endisset
                <x-author-name :author-id="$meal->user_id" :name="$meal->user?->name" />
                <span aria-hidden="true"> · </span>
                <time datetime="{{ $meal->created_at->timezone(config('app.timezone'))->toDateString() }}">{{ $meal->created_at->timezone(config('app.timezone'))->format('Y-m-d') }}</time>
            </span>
        </span>
        <span class="shrink-0 text-muted transition-transform" x-bind:class="open && 'rotate-180'">
            <x-icon name="chevron-down" size="5" stroke="2" />
        </span>
    </button>
    </div>

    <div id="meal-{{ $meal->id }}-details" x-show="open" x-cloak class="space-y-2 pr-4 pb-3.5 pl-12 text-[14px]">
        @if (filled($meal->recipe_link))
            <a href="{{ $meal->recipe_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-tonal px-3 py-1 text-[13px] font-semibold text-tonal-ink">
                <x-icon name="link" size="4" stroke="2" class="shrink-0" />
                <span class="truncate">{{ $meal->recipe_link }}</span>
            </a>
        @endif

        @if (filled($meal->comment))
            <p class="leading-relaxed whitespace-pre-wrap break-words">{{ $meal->comment }}</p>
        @endif

        @unless ($meal->hasDetails())
            <p class="text-muted">{{ __('meals.no_details') }}</p>
        @endunless

        @isset($actions)
            <div class="flex flex-wrap gap-2 pt-1">{{ $actions }}</div>
        @endisset
    </div>
</div>
