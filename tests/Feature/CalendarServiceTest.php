<?php

use App\Models\CalendarDate;
use App\Models\Context;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('builds a run of days without writing anything', function () {
    $this->travelTo('2026-10-27 15:00:00');
    $user = User::factory()->create();

    $days = app(CalendarService::class)->days($user, now(), 10);

    expect($days)->toHaveCount(10)
        ->and($days->first()->datetime)->toBe('2026-10-27')
        ->and($days->get(1)->datetime)->toBe('2026-10-28')
        ->and($days->last()->datetime)->toBe('2026-11-05')
        ->and($days->every(fn (CalendarDate $day): bool => ! $day->exists))->toBeTrue()
        ->and(CalendarDate::query()->count())->toBe(0);
});

it('mixes stored days into the run with one query', function () {
    $this->travelTo('2026-10-27 15:00:00');
    $user = User::factory()->create();
    $contextId = $user->currentContextId();
    $elsewhere = Context::query()->create(['name' => 'Annan']);

    $stored = CalendarDate::query()->create(['context_id' => $contextId, 'date' => '2026-10-29']);
    CalendarDate::query()->create(['context_id' => $elsewhere->id, 'date' => '2026-10-30']);
    CalendarDate::query()->create(['context_id' => $contextId, 'date' => '2026-12-01']);

    $user->currentContextId();
    DB::enableQueryLog();

    $days = app(CalendarService::class)->days($user, now(), 10);

    $calendarQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'calendar_dates'));

    expect($calendarQueries)->toHaveCount(1)
        ->and($days->get(2)->exists)->toBeTrue()
        ->and($days->get(2)->is($stored))->toBeTrue()
        ->and($days->get(3)->exists)->toBeFalse()
        ->and($days->get(3)->datetime)->toBe('2026-10-30');
});

it('never writes a date when the start page is shown', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful();

    expect(CalendarDate::query()->count())->toBe(0);
});

it('finds or creates one row per household and day', function () {
    $user = User::factory()->create();
    $calendar = app(CalendarService::class);

    $first = $calendar->findOrCreate($user, now()->addDays(3));
    $again = $calendar->findOrCreate($user, now()->addDays(3));
    $other = $calendar->findOrCreate($user, now()->addDays(4));

    expect($first->exists)->toBeTrue()
        ->and($again->is($first))->toBeTrue()
        ->and($other->is($first))->toBeFalse()
        ->and($first->context_id)->toBe($user->currentContextId())
        ->and(CalendarDate::query()->count())->toBe(2);
});

it('allows only one row per household and day', function () {
    $contextId = User::factory()->create()->currentContextId();

    CalendarDate::query()->create(['context_id' => $contextId, 'date' => '2026-11-20']);

    expect(fn () => CalendarDate::query()->create(['context_id' => $contextId, 'date' => '2026-11-20']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('removes a stored day once nothing is on it', function () {
    $user = User::factory()->create();
    $calendar = app(CalendarService::class);
    $stored = $calendar->findOrCreate($user, now()->addDay());
    $unsaved = CalendarDate::from(now()->addDays(2), $user->currentContextId());

    $calendar->pruneIfEmpty($stored);
    $calendar->pruneIfEmpty($unsaved);

    expect(CalendarDate::query()->find($stored->id))->toBeNull()
        ->and($unsaved->exists)->toBeFalse();
});
