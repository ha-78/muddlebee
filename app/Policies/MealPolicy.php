<?php

namespace App\Policies;

use App\Enums\Feature;
use App\Enums\MealStatus;
use App\Enums\Role;
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
     * Changing the name, recipe link or comment: the one who added it, or an admin.
     */
    public function update(User $actor, Meal $meal): bool
    {
        if (! $actor->canWrite() || (int) $meal->context_id !== $actor->currentContextId()) {
            return false;
        }

        return (int) $meal->user_id === (int) $actor->getKey() || $actor->role->atLeast(Role::Admin);
    }

    /**
     * Rating a meal 1–5 stars, changing or removing your rating: only on the wishlist, with RateMeals.
     */
    public function rate(User $actor, Meal $meal): bool
    {
        return $actor->canWrite()
            && $actor->allows(Feature::RateMeals)
            && $meal->status === MealStatus::Wishlist
            && (int) $meal->context_id === $actor->currentContextId();
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
