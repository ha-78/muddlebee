{{-- A string of lights hanging under the top bar (Christmas). Each color is its own layer, so the
     layers can twinkle one after another (decor-twinkle in app.css; still with reduced motion). --}}
<svg
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    data-decor="lights"
    {{ $attributes->class('pointer-events-none h-4 w-full') }}
>
    <defs>
        <pattern id="decor-wire" width="60" height="16" patternUnits="userSpaceOnUse">
            <path d="M0 2 Q 30 12 60 2" fill="none" class="stroke-decor-holly-dark" stroke-width="1.5" />
        </pattern>
        <pattern id="decor-bulb-1" width="180" height="16" patternUnits="userSpaceOnUse">
            <rect x="28.5" y="5" width="3" height="3" class="fill-decor-holly-dark" />
            <ellipse cx="30" cy="11" rx="3.5" ry="4.5" class="fill-decor-berry" />
        </pattern>
        <pattern id="decor-bulb-2" width="180" height="16" patternUnits="userSpaceOnUse">
            <rect x="88.5" y="5" width="3" height="3" class="fill-decor-holly-dark" />
            <ellipse cx="90" cy="11" rx="3.5" ry="4.5" class="fill-decor-gold" />
        </pattern>
        <pattern id="decor-bulb-3" width="180" height="16" patternUnits="userSpaceOnUse">
            <rect x="148.5" y="5" width="3" height="3" class="fill-decor-holly-dark" />
            <ellipse cx="150" cy="11" rx="3.5" ry="4.5" class="fill-decor-holly" />
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#decor-wire)" />
    <rect width="100%" height="100%" fill="url(#decor-bulb-1)" class="decor-twinkle" />
    <rect width="100%" height="100%" fill="url(#decor-bulb-2)" class="decor-twinkle [animation-delay:-1s]" />
    <rect width="100%" height="100%" fill="url(#decor-bulb-3)" class="decor-twinkle [animation-delay:-2s]" />
</svg>
