@props(['user' => null, 'name' => null, 'avatar' => null, 'size' => 'md'])

{{-- A round avatar on the current section colour: the emoji someone picked, or else the first letter of their name.
     Pass a user, or a name plus the stored avatar data. Sizes: sm (32 px), md (36 px), lg (52 px). --}}
@php
    $name = $user?->name ?? $name;
    $choice = \App\Support\Avatar::from($user?->avatar ?? $avatar);
@endphp

<span
    aria-hidden="true"
    data-avatar="{{ $choice ? 'emoji' : 'letter' }}"
    {{ $attributes->class([
        'grid shrink-0 place-items-center rounded-full bg-tonal font-semibold text-tonal-ink select-none',
        'size-8' => $size === 'sm',
        'size-9' => $size === 'md',
        'size-13' => $size === 'lg',
        'text-[13px]' => ! $choice && $size === 'sm',
        'text-[14px]' => ! $choice && $size === 'md',
        'text-xl' => ! $choice && $size === 'lg',
        'text-[18px] leading-none' => $choice && $size === 'sm',
        'text-[20px] leading-none' => $choice && $size === 'md',
        'text-[28px] leading-none' => $choice && $size === 'lg',
    ]) }}
>{{ $choice?->emoji ?? Str::upper(Str::substr($name ?? '?', 0, 1)) }}</span>
