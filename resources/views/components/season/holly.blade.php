{{-- A holly sprig: two leaves and three berries. Colors come from the --decor-* tokens in app.css. --}}
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 48 32"
    aria-hidden="true"
    data-decor="holly"
    {{ $attributes->class('pointer-events-none') }}
>
    <path d="M4 16 C 8 4, 20 2, 24 10 C 22 14, 24 18, 22 22 C 14 26, 2 24, 4 16 Z" class="fill-decor-holly stroke-decor-holly-line" stroke-width="1.5" stroke-linejoin="round" />
    <path d="M6 17 Q 14 13 22 15" fill="none" class="stroke-decor-holly-line" stroke-width="1.2" stroke-linecap="round" />
    <path d="M44 14 C 42 4, 30 2, 26 10 C 28 14, 26 18, 28 22 C 36 28, 46 22, 44 14 Z" class="fill-decor-holly-dark stroke-decor-holly-line" stroke-width="1.5" stroke-linejoin="round" />
    <path d="M42 15 Q 34 12 28 15" fill="none" class="stroke-decor-holly-line" stroke-width="1.2" stroke-linecap="round" />
    <circle cx="21" cy="21" r="4.2" class="fill-decor-berry stroke-decor-berry-line" stroke-width="1.5" />
    <circle cx="29" cy="22" r="4.2" class="fill-decor-berry stroke-decor-berry-line" stroke-width="1.5" />
    <circle cx="25" cy="27" r="4.2" class="fill-decor-berry stroke-decor-berry-line" stroke-width="1.5" />
</svg>
