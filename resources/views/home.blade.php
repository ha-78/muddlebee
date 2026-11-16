@extends('layouts.app')

@section('content')
    <x-notice-sheets :default-date="$today->datetime" />

    <div class="space-y-6 lg:grid lg:grid-cols-3 lg:items-start lg:gap-6 lg:space-y-0">
        <div class="min-w-0 space-y-2 lg:col-span-2">
            <x-calendar :today="$today" :tomorrow="$tomorrow" :days="$days" />

            <div class="flex justify-end px-1">
                <a href="{{ route('notices.index') }}" class="inline-flex items-center gap-0.5 text-[13px] font-medium text-muted">
                    {{ __('notices.all') }}
                    <x-icon name="chevron-right" size="4" stroke="2" />
                </a>
            </div>
        </div>

        {{-- Threads keep their own green here. --}}
        <section data-color="{{ \App\Enums\Section::Threads->color() }}" class="min-w-0 space-y-3">
            <div class="flex items-baseline justify-between gap-3 px-1">
                <h2 class="text-[13px] font-semibold uppercase tracking-wide text-muted">{{ __('threads.threads') }}</h2>
                <a href="{{ route('threads.index') }}" class="inline-flex items-center gap-0.5 text-[13px] font-medium text-muted">
                    {{ __('threads.show_threads') }}
                    <x-icon name="chevron-right" size="4" stroke="2" />
                </a>
            </div>

            @can('create', App\Models\Thread::class)
                <x-ui.quick-add
                    :label="__('threads.new_thread')"
                    :placeholder="__('threads.new')"
                    name="body"
                    :action="route('threads.store')"
                    :value="old('body')"
                    multiline
                    required
                    maxlength="10000"
                >
                    <x-slot:hidden>
                        <input type="hidden" name="return_to" value="home">
                    </x-slot:hidden>
                </x-ui.quick-add>
            @endcan

            <div x-data="liveRegion" data-fragment="thread-list" data-version="{{ $version }}" data-version-url="{{ route('threads.version') }}">
                <div x-ref="region" class="home-threads">
                    <x-thread-list :threads="$threads" :empty-message="$emptyMessage" />
                </div>
            </div>
        </section>
    </div>
@endsection
