{{-- A few snowflakes drifting down through the top bar (Christmas). They stand still with reduced motion. --}}
@php
    // left (%), size (px), duration (s), delay (s), resting height (px) when motion is reduced
    $flakes = [[6, 3, 7, 0, 10], [17, 2, 9, -3, 34], [29, 4, 8, -6, 18], [41, 2, 10, -1, 44], [53, 3, 7.5, -4, 8], [64, 2, 9.5, -7, 28], [76, 4, 8.5, -2, 40], [88, 3, 10.5, -5, 14], [95, 2, 7, -8, 32]];
@endphp

<div aria-hidden="true" data-decor="snow" {{ $attributes->class('pointer-events-none absolute inset-0 overflow-hidden') }}>
    @foreach ($flakes as [$left, $size, $duration, $delay, $rest])
        <span
            class="decor-snow absolute top-0 rounded-full bg-decor-snow"
            style="left: {{ $left }}%; width: {{ $size }}px; height: {{ $size }}px; animation-duration: {{ $duration }}s; animation-delay: {{ $delay }}s; --decor-rest: {{ $rest }}px;"
        ></span>
    @endforeach
</div>
