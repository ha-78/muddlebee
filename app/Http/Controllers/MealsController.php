<?php

namespace App\Http\Controllers;

use App\Enums\MealStatus;
use App\Http\Requests\PlanMealRequest;
use App\Http\Requests\StoreMealRequest;
use App\Http\Requests\UnplanMealRequest;
use App\Http\Requests\UpdateMealRequest;
use App\Http\Requests\UpdateMealStatusRequest;
use App\Http\Requests\WishMealAgainRequest;
use App\Models\Meal;
use App\Services\CalendarService;
use App\Services\MealService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MealsController extends Controller
{
    public function index(Request $request, MealService $meals, CalendarService $calendar): View
    {
        Gate::authorize('viewAny', Meal::class);

        $user = $request->user();
        $status = MealStatus::fromTab($request->query('status'));

        if ($status === MealStatus::Planned) {
            $days = $calendar->days($user, today(), CalendarService::HORIZON);
            Collection::make($days->flatMap(fn ($day) => $day->loaded('meals'))->all())->load('user');

            return view('meals.index', [
                'status' => $status,
                'meals' => collect(),
                'days' => $days,
                'later' => $meals->plannedFrom($user, today()->addDays(CalendarService::HORIZON)),
                'earlier' => $meals->plannedBefore($user, today()),
                'shortlist' => $meals->list($user, MealStatus::Shortlist),
                'highlight' => (string) $request->query('date'),
            ]);
        }

        $sort = $request->query('sort') === MealService::SORT_RATING ? MealService::SORT_RATING : MealService::SORT_NEWEST;

        return view('meals.index', [
            'status' => $status,
            'sort' => $sort,
            'meals' => $meals->list($user, $status, $sort),
        ]);
    }

    public function plan(PlanMealRequest $request, Meal $meal, MealService $meals): RedirectResponse|Response
    {
        $meal = $meals->plan($request->user(), $meal, $request->day());

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back(fallback: route('meals.index', ['status' => 'planned']))
            ->with('status', __('meals.planned_on', ['name' => $meal->name, 'day' => $meal->calendarDate->heading]));
    }

    public function unplan(UnplanMealRequest $request, Meal $meal, MealService $meals): RedirectResponse|Response
    {
        $meals->unplan($request->user(), $meal);

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back(fallback: route('meals.index', ['status' => 'planned']))
            ->with('status', __('meals.unplanned', ['name' => $meal->name]));
    }

    public function again(WishMealAgainRequest $request, Meal $meal, MealService $meals): RedirectResponse
    {
        $meals->wishAgain($request->user(), $meal);

        return back(fallback: route('meals.index', ['status' => 'planned']))
            ->with('status', __('meals.wished_again', ['name' => $meal->name]));
    }

    public function store(StoreMealRequest $request, MealService $meals): RedirectResponse
    {
        $meals->create(
            $request->user(),
            $request->validated('name'),
            $request->validated('recipe_link'),
            $request->validated('comment'),
        );

        return to_route('meals.index')->with('status', __('meals.created'));
    }

    public function update(UpdateMealRequest $request, Meal $meal, MealService $meals): RedirectResponse
    {
        $meals->update(
            $request->user(),
            $meal,
            $request->validated('name'),
            $request->validated('recipe_link'),
            $request->validated('comment'),
        );

        return back(fallback: route('meals.index', ['status' => $meal->status->value]))
            ->with('status', __('meals.updated'));
    }

    public function updateStatus(UpdateMealStatusRequest $request, Meal $meal, MealService $meals): RedirectResponse
    {
        $from = $meal->status;
        $target = $request->target();

        $meals->moveTo($request->user(), $meal, $target);

        return to_route('meals.index', ['status' => $from->value])
            ->with('status', __('meals.moved.'.$target->value, ['name' => $meal->name]));
    }
}
