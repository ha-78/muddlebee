<?php

namespace App\Services;

use App\Models\CalendarDate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * How many days ahead the start page and the meal planner show, from today.
     */
    public const HORIZON = 10;

    /**
     * A run of days from $from. Days with something on them come from the database
     * (one query for the whole run, plus one each for their notices and meals); the others are unsaved.
     * Never writes.
     *
     * @return Collection<int, CalendarDate>
     */
    public function days(User $user, CarbonInterface $from, int $count): Collection
    {
        $contextId = $user->currentContextId();
        $start = CarbonImmutable::instance($from)->timezone(config('app.timezone'))->startOfDay();
        $end = $start->addDays($count - 1);

        $stored = CalendarDate::query()
            ->with(['notices' => fn ($query) => $query->visibleTo($user)->inDayOrder(), 'meals'])
            ->where('context_id', $contextId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (CalendarDate $date): string => $date->datetime);

        return collect(range(0, $count - 1))->map(function (int $offset) use ($start, $stored, $contextId): CalendarDate {
            $day = $start->addDays($offset);

            return $stored->get($day->toDateString()) ?? CalendarDate::from($day, $contextId);
        });
    }

    /**
     * The stored row for a day in the user's household, created if it isn't there.
     * Call it inside the transaction that puts something on the day.
     */
    public function findOrCreate(User $user, CarbonInterface $day): CalendarDate
    {
        return CalendarDate::query()->firstOrCreate([
            'context_id' => $user->currentContextId(),
            'date' => $day->copy()->timezone(config('app.timezone'))->toDateString(),
        ]);
    }

    /**
     * Remove a stored day once nothing is left on it.
     */
    public function pruneIfEmpty(CalendarDate $date): void
    {
        if ($date->exists && $date->isEmpty()) {
            $date->delete();
        }
    }
}
