<?php

use App\Models\CalendarDate;
use Tests\TestCase;

uses(TestCase::class);

it('formats a calendar day in the configured locale', function () {
    $locale = config('app.locale');
    $this->travelTo('2026-10-27 15:00:00');

    try {
        config(['app.locale' => 'sv']);

        $swedish = CalendarDate::from(now());

        expect($swedish->exists)->toBeFalse()
            ->and($swedish->datetime)->toBe('2026-10-27')
            ->and($swedish->day)->toBe('27')
            ->and($swedish->month)->toBe('oktober')
            ->and($swedish->weekday)->toBe('tisdag');

        config(['app.locale' => 'en']);

        $english = CalendarDate::from(now());

        expect($english->datetime)->toBe('2026-10-27')
            ->and($english->day)->toBe('27')
            ->and($english->month)->toBe('October')
            ->and($english->weekday)->toBe('Tuesday');
    } finally {
        config(['app.locale' => $locale]);
    }
});

it('keeps the day the same in the app time zone', function () {
    $this->travelTo('2026-10-27 23:30:00');

    $date = CalendarDate::from(now()->utc(), 5);

    expect($date->datetime)->toBe(now()->timezone(config('app.timezone'))->toDateString())
        ->and($date->context_id)->toBe(5)
        ->and($date->exists)->toBeFalse();
});
