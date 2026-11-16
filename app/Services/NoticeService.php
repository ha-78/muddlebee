<?php

namespace App\Services;

use App\Models\CalendarDate;
use App\Models\Notice;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NoticeService
{
    public function __construct(private CalendarService $calendar) {}

    /**
     * Today and later days that have notices the user may see, soonest first.
     *
     * @return Collection<int, CalendarDate>
     */
    public function upcoming(User $user): Collection
    {
        return $this->daysWithNotices($user)
            ->where('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->get();
    }

    /**
     * Earlier days with notices the user may see, newest first.
     *
     * @return LengthAwarePaginator<int, CalendarDate>
     */
    public function past(User $user): LengthAwarePaginator
    {
        return $this->daysWithNotices($user)
            ->where('date', '<', today()->toDateString())
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return Builder<CalendarDate>
     */
    private function daysWithNotices(User $user): Builder
    {
        return CalendarDate::query()
            ->where('context_id', $user->currentContextId())
            ->whereHas('notices', fn (Builder $query) => $query->visibleTo($user))
            ->with(['notices' => fn ($query) => $query->visibleTo($user)->inDayOrder()->with('user')]);
    }

    public function create(User $actor, CarbonInterface $day, string $body, ?string $time, bool $isPublic): Notice
    {
        if (! $actor->can('create', Notice::class)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $day, $body, $time, $isPublic): Notice {
            return $this->calendar->findOrCreate($actor, $day)->notices()->create([
                'user_id' => $actor->getKey(),
                'body' => $body,
                'time' => $time,
                'is_public' => $isPublic,
            ]);
        });
    }

    /**
     * Change a notice, including its day. The old day is removed if it is left empty.
     */
    public function update(User $actor, Notice $notice, CarbonInterface $day, string $body, ?string $time, bool $isPublic): Notice
    {
        if (! $actor->can('update', $notice)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $notice, $day, $body, $time, $isPublic): Notice {
            $oldDate = $notice->calendarDate;
            $newDate = $this->calendar->findOrCreate($actor, $day);

            $notice->update([
                'calendar_date_id' => $newDate->getKey(),
                'body' => $body,
                'time' => $time,
                'is_public' => $isPublic,
            ]);

            if (! $oldDate->is($newDate)) {
                $this->calendar->pruneIfEmpty($oldDate);
            }

            return $notice;
        });
    }

    public function delete(User $actor, Notice $notice): void
    {
        if (! $actor->can('delete', $notice)) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($notice): void {
            $date = $notice->calendarDate;
            $notice->delete();
            $this->calendar->pruneIfEmpty($date);
        });
    }
}
