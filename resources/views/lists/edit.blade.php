@extends('layouts.app')

@section('title', __('lists.edit'))

@section('content')
    <div class="space-y-6">
        <x-lists.edit-list-sheet />

        {{-- Active lists (with the new-list card) or the archived ones, like the threads archive. --}}
        <x-ui.segmented
            :label="__('lists.edit')"
            :options="[
                ['label' => __('lists.active_heading'), 'href' => route('lists.edit'), 'selected' => ! $showingArchived],
                ['label' => __('lists.archived_heading'), 'href' => route('lists.edit', ['archived' => 1]), 'selected' => $showingArchived],
            ]"
        />

        @if ($showingArchived)
            @if ($archived->isEmpty())
                <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ __('lists.archived_empty') }}</p>
            @else
                <x-ui.group :heading="__('lists.archived_heading')">
                    @foreach ($archived as $list)
                        <x-item-list :list="$list" />
                    @endforeach
                </x-ui.group>
            @endif
        @else
            <x-ui.quick-add :label="__('lists.new_list')" :placeholder="__('lists.name')" name="name" :action="route('lists.store')" :value="old('name')">
                @can('publish', App\Models\ItemList::class)
                    <x-ui.switch :label="__('lists.is_public')" name="is_public" :checked="old('is_public')" icon="globe" />
                @endcan
            </x-ui.quick-add>

            @if ($shared->isEmpty() && $private->isEmpty())
                <p class="rounded-card border border-surface-line bg-surface px-4 py-3 text-[15px] text-muted">{{ __('lists.empty') }}</p>
            @endif

            @if ($shared->isNotEmpty())
                <x-ui.group :heading="__('lists.shared')">
                    @foreach ($shared as $list)
                        <x-item-list :list="$list" />
                    @endforeach
                </x-ui.group>
            @endif

            @if ($private->isNotEmpty())
                <x-ui.group :heading="__('lists.private')">
                    @foreach ($private as $list)
                        <x-item-list :list="$list" />
                    @endforeach
                </x-ui.group>
            @endif
        @endif
    </div>
@endsection
