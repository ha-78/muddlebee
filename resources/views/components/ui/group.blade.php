@props([
    'heading' => null,
])

<section {{ $attributes }}>
    @if (filled($heading) || isset($actions))
        <div class="flex items-baseline justify-between gap-3 px-1 pb-1.5">
            <div class="flex min-w-0 items-center gap-2">
                @if (filled($heading))
                    <h2 class="text-[13px] font-semibold uppercase tracking-wide text-muted">{{ $heading }}</h2>
                @endif

                {{ $badge ?? '' }}
            </div>

            @isset($actions)
                <div class="text-[13px] font-medium text-muted">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @if (\App\Enums\Season::is(\App\Enums\Season::Christmas))
        {{-- A holly sprig on the card's top-right corner (APP_SEASON=christmas). --}}
        <div class="relative">
            <div class="divide-y divide-surface-line overflow-hidden rounded-card border border-surface-line bg-surface">
                {{ $slot }}
            </div>
            <x-season.holly class="absolute -top-3 -right-2 z-10 w-10" />
        </div>
    @else
        <div class="divide-y divide-surface-line overflow-hidden rounded-card border border-surface-line bg-surface">
            {{ $slot }}
        </div>
    @endif
</section>
