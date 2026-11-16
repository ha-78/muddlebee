@props(['date'])

<x-calendar.day :date="$date" :label="__('calendar.today')" big {{ $attributes }} />
