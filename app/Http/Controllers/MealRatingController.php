<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateMealRequest;
use App\Http\Requests\UnrateMealRequest;
use App\Models\Meal;
use App\Services\MealRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MealRatingController extends Controller
{
    /**
     * Rate a meal, or change your rating. JSON requests get the new summary so the stars can update in place.
     */
    public function update(RateMealRequest $request, Meal $meal, MealRatingService $ratings): JsonResponse|RedirectResponse
    {
        $ratings->rate($request->user(), $meal, $request->integer('rating'));

        if ($request->expectsJson()) {
            return response()->json($ratings->summary($meal, $request->user()));
        }

        return back(fallback: route('meals.index'))->with('status', __('meals.rated', ['name' => $meal->name]));
    }

    public function destroy(UnrateMealRequest $request, Meal $meal, MealRatingService $ratings): JsonResponse|RedirectResponse
    {
        $ratings->unrate($request->user(), $meal);

        if ($request->expectsJson()) {
            return response()->json($ratings->summary($meal, $request->user()));
        }

        return back(fallback: route('meals.index'))->with('status', __('meals.unrated', ['name' => $meal->name]));
    }
}
