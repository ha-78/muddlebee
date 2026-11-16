@props(['today', 'tomorrow', 'days'])

<div {{ $attributes->class('space-y-2.5') }}>
    <x-calendar.today :date="$today" />
    <x-calendar.tomorrow :date="$tomorrow" />

    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
        @foreach ($days as $day)
            <x-calendar.day :date="$day" />
        @endforeach
    </div>
</div>
