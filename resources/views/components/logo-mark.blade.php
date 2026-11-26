{{-- The Muddlebee bee: body from the side, head turned to face us. Colors come from the --logo-* tokens in app.css.
     Decorative by default; pass a label to make it an image on its own. --}}
@props(['label' => null])

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 100 100"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    {{ $attributes->class('shrink-0') }}
>
    <g class="stroke-logo-line" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
        <ellipse cx="56" cy="34" rx="11" ry="16" transform="rotate(-18 56 34)" class="fill-logo-wing" />
        <ellipse cx="70" cy="36" rx="10" ry="14" transform="rotate(22 70 36)" class="fill-logo-wing" />
        <path d="M26.5 48 Q24 37 18 32" fill="none" />
        <circle cx="17" cy="31" r="3.4" class="fill-logo-line" />
        <path d="M35.5 48 Q38 37 44 32" fill="none" />
        <circle cx="45" cy="31" r="3.4" class="fill-logo-line" />
        <path d="M50 49 C 66 43, 86 51, 89 60 C 86 69, 66 77, 50 71 Z" class="fill-logo-light" />
        <circle cx="47" cy="60" r="12" class="fill-logo-mid" />
        <circle cx="31" cy="60" r="13.5" class="fill-logo-light" />
    </g>
    <circle cx="26" cy="58" r="3.1" class="fill-logo-line" />
    <circle cx="36" cy="58" r="3.1" class="fill-logo-line" />
    <circle cx="25.1" cy="57" r="0.95" class="fill-logo-wing" />
    <circle cx="35.1" cy="57" r="0.95" class="fill-logo-wing" />
    <path d="M27 64.5 Q31 68.5 35 64.5" fill="none" class="stroke-logo-line" stroke-width="2.6" stroke-linecap="round" />

    @if (\App\Enums\Season::is(\App\Enums\Season::Christmas))
        {{-- Santa hat on the head (APP_SEASON=christmas). --}}
        <g data-decor="hat" class="stroke-logo-line" stroke-width="3" stroke-linejoin="round">
            <path d="M17 50 Q 22 24 46 22 Q 56 30 47 48 Z" class="fill-decor-hat" />
            <rect x="14" y="44" width="36" height="9" rx="4.5" class="fill-decor-fur" stroke-width="2.5" />
            <circle cx="48" cy="22" r="6" class="fill-decor-fur" stroke-width="2.5" />
        </g>
    @endif
</svg>
