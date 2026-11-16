<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A short note on a day ("Notis"), shared with the household or private to its author.
 */
#[Fillable(['calendar_date_id', 'user_id', 'body', 'time', 'is_public'])]
class Notice extends Model
{
    protected $attributes = [
        'is_public' => true,
    ];

    /**
     * Shared notices, plus the user's own private ones.
     *
     * @param  Builder<Notice>  $query
     * @return Builder<Notice>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $userId = $query->qualifyColumn('user_id');
        $isPublic = $query->qualifyColumn('is_public');

        return $query->where(function (Builder $query) use ($user, $userId, $isPublic): void {
            $query->where($isPublic, true)
                ->orWhere($userId, $user->getKey());
        });
    }

    /**
     * Timed notices first by time, then the rest in the order they were added.
     *
     * @param  Builder<Notice>  $query
     * @return Builder<Notice>
     */
    public function scopeInDayOrder(Builder $query): Builder
    {
        $time = $query->qualifyColumn('time');

        return $query->orderByRaw($time.' is null')
            ->orderBy($time)
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * What the "Ändra notis" sheet needs to fill itself in.
     *
     * @return array{id: int, action: string, destroy: string, date: string, body: string, time: string, private: bool}
     */
    public function toEditor(): array
    {
        return [
            'id' => $this->getKey(),
            'action' => route('notices.update', $this),
            'destroy' => route('notices.destroy', $this),
            'date' => $this->calendarDate->datetime,
            'body' => $this->body,
            'time' => $this->short_time ?? '',
            'private' => ! $this->is_public,
        ];
    }

    /**
     * @return BelongsTo<CalendarDate, $this>
     */
    public function calendarDate(): BelongsTo
    {
        return $this->belongsTo(CalendarDate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The time as HH:MM, or null.
     *
     * @return Attribute<?string, never>
     */
    protected function shortTime(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->time === null ? null : substr((string) $this->time, 0, 5));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }
}
