@extends('layouts.app')

@section('title', __('notices.all'))
@section('back', route('home'))

@can('create', App\Models\Notice::class)
    @section('actions')
        <x-ui.sheet-trigger for="new-notice" variant="primary" icon :label="__('notices.new')">
            <x-icon name="plus" size="5" stroke="1.8" />
        </x-ui.sheet-trigger>
    @endsection
@endcan

@section('content')
    <x-notice-sheets :default-date="today()->toDateString()" />

    <div class="space-y-5">
        <x-ui.segmented
            :label="__('notices.all')"
            :options="[
                ['label' => __('notices.upcoming'), 'href' => route('notices.index'), 'selected' => ! $past],
                ['label' => __('notices.past'), 'href' => route('notices.index', ['past' => 1]), 'selected' => $past],
            ]"
        />

        @forelse ($days as $day)
            <x-ui.group :heading="$day->heading">
                @if (! $past && $day->date->isToday())
                    <x-slot:badge><x-ui.badge>{{ __('calendar.today') }}</x-ui.badge></x-slot:badge>
                @elseif (! $past && $day->date->isTomorrow())
                    <x-slot:badge><x-ui.badge>{{ __('calendar.tomorrow') }}</x-ui.badge></x-slot:badge>
                @endif

                @foreach ($day->notices as $notice)
                    <x-notice-row :notice="$notice" :editable="! $past" />
                @endforeach
            </x-ui.group>
        @empty
            <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ $past ? __('notices.empty_past') : __('notices.empty_upcoming') }}</p>
        @endforelse

        @if ($past && $days->hasPages())
            <div>{{ $days->links() }}</div>
        @endif
    </div>
@endsection
