<?php

namespace App\Policies;

use App\Enums\Feature;
use App\Models\Meal;
use App\Models\User;

class MealPolicy
{
    public function viewAny(User $actor): bool
    {
        return true;
    }

    public function create(User $actor): bool
    {
        return $actor->canWrite() && $actor->allows(Feature::AddMealsToWishlist);
    }

    /**
     * Putting meals on a day, moving them and taking them off. Without a meal: may plan at all.
     */
    public function plan(User $actor, ?Meal $meal = null): bool
    {
        return $actor->canWrite()
            && $actor->allows(Feature::PlanMeals)
            && ($meal === null || (int) $meal->context_id === $actor->currentContextId());
    }

    /**
     * Copying a planned meal back onto the wishlist.
     */
    public function wishAgain(User $actor, Meal $meal): bool
    {
        return $this->create($actor) && (int) $meal->context_id === $actor->currentContextId();
    }

    /**
     * Moving between the wishlist, the shortlist and discarded.
     */
    public function move(User $actor, Meal $meal): bool
    {
        return $actor->canWrite()
            && $actor->allows(Feature::AddMealsToShortlist)
            && (int) $meal->context_id === $actor->currentContextId();
    }
}
