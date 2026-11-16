@props(['notice', 'editable' => true])

{{-- One notice in the "Alla notiser" list: text, then who and when. Opens the edit sheet for
     those who may change it, unless the list is read-only (earlier days). --}}
@php($canEdit = $editable && auth()->user()?->can('update', $notice))

@if ($canEdit)
    <button
        type="button"
        x-data
        x-on:click="$dispatch('edit-notice', {{ Js::from($notice->toEditor()) }}); $dispatch('open-sheet', 'edit-notice')"
        class="flex min-h-13 w-full items-center gap-3 px-4 py-2 text-left text-ink"
    >
@else
    <div class="flex min-h-13 items-center gap-3 px-4 py-2 text-ink">
@endif
        <span class="min-w-0 flex-1">
            <span class="block text-[15px] font-semibold break-words">{{ $notice->body }}</span>
            <span class="flex items-center gap-1.5 text-[13px] text-muted">
                <x-author-name :author-id="$notice->user_id" :name="$notice->user?->name" />
                @unless ($notice->is_public)
                    <span aria-hidden="true">·</span>
                    <x-icon name="lock" size="3.5" stroke="2" />
                    <span>{{ __('notices.private') }}</span>
                @endunless
            </span>
        </span>
        @if ($notice->short_time)
            <span class="shrink-0 text-[15px] font-semibold tabular-nums">{{ $notice->short_time }}</span>
        @endif
        @if ($canEdit)
            <span class="shrink-0 text-muted"><x-icon name="chevron-right" size="4" stroke="2" /></span>
        @endif
@if ($canEdit)
    </button>
@else
    </div>
@endif
