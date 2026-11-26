@props([
    'href' => null,
])

<header class="sticky top-0 z-30 h-14 bg-bar text-ink">
    @if (\App\Enums\Season::is(\App\Enums\Season::Christmas))
        <x-season.snow />
        <x-season.lights class="absolute inset-x-0 top-full" />
    @endif

    <div class="relative mx-auto flex h-full w-full max-w-5xl items-center gap-1 px-2 sm:px-4">
        @isset($leading)
            {{ $leading }}
        @endisset

        @if ($href)
            <a href="{{ $href }}" class="min-w-0 px-1" aria-label="{{ __('layout.brand') }}"><x-logo /></a>
        @elseif (isset($title))
            <h1 class="min-w-0 flex-1 truncate px-2 text-xl font-semibold">{{ $title }}</h1>
            <div class="shrink-0">{{ $slot }}</div>
        @else
            <div class="min-w-0 flex-1">{{ $slot }}</div>
        @endif
    </div>
</header>
