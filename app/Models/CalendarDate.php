<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One day in a household's calendar.
 *
 * Stored only while something is on it; for an empty day the app uses an unsaved
 * instance from from(). The display values (datetime, day, month, weekday) work the
 * same for both.
 */
#[Fillable(['context_id', 'date'])]
class CalendarDate extends Model
{
    /**
     * Relations that count as "something on this day". A day with none of them is
     * empty and is removed by CalendarService::pruneIfEmpty(). Notices and planned
     * meals are added here when they exist.
     *
     * @var list<string>
     */
    public const CONTENT = ['notices', 'meals'];

    /**
     * An unsaved day for the given moment, in the app's time zone.
     */
    public static function from(CarbonInterface $moment, ?int $contextId = null): self
    {
        return new self([
            'context_id' => $contextId,
            'date' => $moment->copy()->timezone(config('app.timezone'))->toDateString(),
        ]);
    }

    public function isEmpty(): bool
    {
        foreach (self::CONTENT as $relation) {
            if ($this->{$relation}()->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * A relation's models if it was loaded, otherwise an empty collection, so a view
     * can list a day's notices or meals for stored and unsaved days alike.
     *
     * @return Collection<int, Model>
     */
    public function loaded(string $relation): Collection
    {
        return $this->relationLoaded($relation) ? $this->getRelation($relation) : new Collection;
    }

    /**
     * @return HasMany<Notice, $this>
     */
    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class)->chaperone('calendarDate');
    }

    /**
     * Meals planned on this day, in the order they were planned.
     *
     * @return HasMany<Meal, $this>
     */
    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class)->chaperone('calendarDate')->orderBy('planned_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<Context, $this>
     */
    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function datetime(): Attribute
    {
        return Attribute::get(fn (): string => $this->date->toDateString());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function day(): Attribute
    {
        return Attribute::get(fn (): string => $this->localDate()->isoFormat('D'));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function month(): Attribute
    {
        return Attribute::get(fn (): string => $this->localDate()->isoFormat('MMMM'));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function weekday(): Attribute
    {
        return Attribute::get(fn (): string => $this->localDate()->isoFormat('dddd'));
    }

    /**
     * "tisdag 17 november", with the year added when it isn't this year.
     *
     * @return Attribute<string, never>
     */
    protected function heading(): Attribute
    {
        return Attribute::get(function (): string {
            $date = $this->localDate();

            return $date->isoFormat($date->year === now()->year ? 'dddd D MMMM' : 'dddd D MMMM YYYY');
        });
    }

    private function localDate(): CarbonInterface
    {
        return $this->date->locale(config('app.locale'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }
}
