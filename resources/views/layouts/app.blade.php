<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-section="{{ $section->color() }}"
    data-color="{{ $section->color() }}"
    @if (\App\Enums\Season::current()) data-season="{{ \App\Enums\Season::current()->value }}" @endif
    data-posted-today="{{ __('threads.today') }}"
    data-posted-yesterday="{{ __('threads.yesterday') }}"
    data-posted-days-ago="{{ __('threads.days_ago_phrase') }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-theme-script />
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <x-site-header>
            <x-slot:leading>
                @if ($__env->hasSection('back') || ($section->routeName() && ! $section->isMainPage()))
                    <x-section-back :section="$section" :href="trim($__env->yieldContent('back'))" />
                @endif
                {{-- The bee on every page, left of the section title. --}}
                <x-logo-mark class="ml-1 size-8" />
            </x-slot:leading>
            <x-slot:title>
                @hasSection('title')
                    @yield('title')
                @else
                    {{ $section->label() }}
                @endif
            </x-slot:title>
            <div class="flex items-center gap-2">
                @hasSection('actions')
                    <div class="flex items-center gap-2 sm:hidden">@yield('actions')</div>
                @endif
                <x-section-nav variant="top" :current="$section" />
            </div>
        </x-site-header>
        <x-section-nav variant="tabs" :current="$section" />
        <main class="mx-auto max-w-5xl px-4 pt-8 pb-24 sm:pb-8">
            @hasSection('actions')
                <div class="-mt-4 mb-4 hidden items-center justify-end gap-2 sm:flex">@yield('actions')</div>
            @endif
            @if (session('status'))
                <x-ui.message class="mb-6">{{ session('status') }}</x-ui.message>
            @endif
            @if (session('error'))
                <x-ui.message type="error" class="mb-6">{{ session('error') }}</x-ui.message>
            @endif
            @yield('content')
        </main>
    </body>
</html>
