@props(['notice', 'big' => false])

{{-- One notice in a day box. Your own (or, for an admin, a shared one) opens the "Ändra notis" sheet. --}}
@php($classes = ['flex w-full items-center gap-2 rounded-field bg-surface text-left', 'min-h-9 px-2.5 text-[14px]' => $big, 'min-h-7 px-2 text-[13px]' => ! $big])

@can('update', $notice)
    <button
        type="button"
        x-data
        x-on:click="$dispatch('edit-notice', {{ Js::from($notice->toEditor()) }}); $dispatch('open-sheet', 'edit-notice')"
        @class($classes)
    >
@else
    <span @class($classes)>
@endcan
        <span class="min-w-0 flex-1 truncate">{{ $notice->body }}</span>
        @unless ($notice->is_public)
            <span class="shrink-0" title="{{ __('notices.private_badge') }}">
                <x-icon name="lock" size="3.5" stroke="2" />
                <span class="sr-only">{{ __('notices.private_badge') }}</span>
            </span>
        @endunless
        @if ($notice->short_time)
            <span class="shrink-0 font-semibold tabular-nums">{{ $notice->short_time }}</span>
        @endif
@can('update', $notice)
    </button>
@else
    </span>
@endcan
