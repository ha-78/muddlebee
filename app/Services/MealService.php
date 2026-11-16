<?php

namespace App\Services;

use App\Enums\MealStatus;
use App\Models\Meal;
use Carbon\CarbonInterface;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MealService
{
    public function __construct(private CalendarService $calendar) {}

    /**
     * @return Collection<int, Meal>
     */
    public function list(User $user, MealStatus $status): Collection
    {
        return Meal::query()
            ->with('user')
            ->forContext($user)
            ->where('status', $status)
            ->orderByDesc('id')
            ->get();
    }

    public function create(User $owner, string $name, ?string $recipeLink, ?string $comment): Meal
    {
        if (! $owner->can('create', Meal::class)) {
            throw new AuthorizationException;
        }

        return $owner->meals()->create([
            'context_id' => $owner->currentContextId(),
            'name' => $name,
            'recipe_link' => $recipeLink,
            'comment' => $comment,
            'status' => MealStatus::Wishlist,
        ]);
    }

    public function moveTo(User $actor, Meal $meal, MealStatus $target): void
    {
        if (! $actor->can('move', $meal)) {
            throw new AuthorizationException;
        }

        if (! $meal->status->canMoveTo($target)) {
            throw ValidationException::withMessages([
                'status' => __('meals.move_refused'),
            ]);
        }

        $meal->update(['status' => $target]);
    }

    /**
     * Planned meals on days from $from (inclusive), soonest first.
     *
     * @return Collection<int, Meal>
     */
    public function plannedFrom(User $user, CarbonInterface $from): Collection
    {
        return $this->planned($user)
            ->join('calendar_dates', 'calendar_dates.id', '=', 'meals.calendar_date_id')
            ->where('calendar_dates.date', '>=', $from->toDateString())
            ->orderBy('calendar_dates.date')
            ->orderBy('meals.planned_at')
            ->orderBy('meals.id')
            ->select('meals.*')
            ->get();
    }

    /**
     * Planned meals on days before $before, newest first, at most $limit.
     *
     * @return Collection<int, Meal>
     */
    public function plannedBefore(User $user, CarbonInterface $before, int $limit = 20): Collection
    {
        return $this->planned($user)
            ->join('calendar_dates', 'calendar_dates.id', '=', 'meals.calendar_date_id')
            ->where('calendar_dates.date', '<', $before->toDateString())
            ->orderByDesc('calendar_dates.date')
            ->orderByDesc('meals.planned_at')
            ->select('meals.*')
            ->limit($limit)
            ->get();
    }

    /**
     * Change a meal's name, recipe link and comment. Its status and day stay as they are.
     */
    public function update(User $actor, Meal $meal, string $name, ?string $recipeLink, ?string $comment): Meal
    {
        if (! $actor->can('update', $meal)) {
            throw new AuthorizationException;
        }

        $meal->update([
            'name' => $name,
            'recipe_link' => $recipeLink,
            'comment' => $comment,
        ]);

        return $meal;
    }

    /**
     * Put a shortlisted meal on a day, or move a planned meal to another day.
     * The old day is removed if it is left empty.
     */
    public function plan(User $actor, Meal $meal, CarbonInterface $day): Meal
    {
        if (! $actor->can('plan', $meal)) {
            throw new AuthorizationException;
        }

        if (! in_array($meal->status, [MealStatus::Shortlist, MealStatus::Planned], true)) {
            throw ValidationException::withMessages([
                'meal' => __('meals.plan_refused'),
            ]);
        }

        return DB::transaction(function () use ($actor, $meal, $day): Meal {
            $oldDate = $meal->calendarDate;
            $newDate = $this->calendar->findOrCreate($actor, $day);

            if ($oldDate?->is($newDate)) {
                return $meal;
            }

            $meal->update([
                'status' => MealStatus::Planned,
                'calendar_date_id' => $newDate->getKey(),
                'planned_at' => now(),
            ]);

            if ($oldDate !== null) {
                $this->calendar->pruneIfEmpty($oldDate);
            }

            return $meal->setRelation('calendarDate', $newDate);
        });
    }

    /**
     * Take a planned meal off its day and put it back on the shortlist.
     */
    public function unplan(User $actor, Meal $meal): void
    {
        if (! $actor->can('plan', $meal)) {
            throw new AuthorizationException;
        }

        if ($meal->status !== MealStatus::Planned) {
            throw ValidationException::withMessages([
                'meal' => __('meals.unplan_refused'),
            ]);
        }

        DB::transaction(function () use ($meal): void {
            $oldDate = $meal->calendarDate;

            $meal->update([
                'status' => MealStatus::Shortlist,
                'calendar_date_id' => null,
                'planned_at' => null,
            ]);

            if ($oldDate !== null) {
                $this->calendar->pruneIfEmpty($oldDate);
            }
        });
    }

    /**
     * A new wish with the same name, link and comment as a planned meal.
     */
    public function wishAgain(User $actor, Meal $meal): Meal
    {
        if (! $actor->can('wishAgain', $meal)) {
            throw new AuthorizationException;
        }

        return $this->create($actor, $meal->name, $meal->recipe_link, $meal->comment);
    }

    /**
     * @return Builder<Meal>
     */
    private function planned(User $user): Builder
    {
        return Meal::query()
            ->with(['user', 'calendarDate'])
            ->forContext($user)
            ->where('meals.status', MealStatus::Planned);
    }
}
