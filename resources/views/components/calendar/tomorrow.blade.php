@props(['date'])

<x-calendar.day :date="$date" :label="__('calendar.tomorrow')" big {{ $attributes }} />
