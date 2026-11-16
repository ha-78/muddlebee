<?php

namespace App\Policies;

use App\Enums\Feature;
use App\Enums\Role;
use App\Models\Notice;
use App\Models\User;

class NoticePolicy
{
    public function create(User $actor): bool
    {
        return $actor->canWrite() && $actor->allows(Feature::CreateNotifications);
    }

    /**
     * The author, or an admin for a shared notice. Private notices are only the author's.
     */
    public function update(User $actor, Notice $notice): bool
    {
        if (! $actor->canWrite() || (int) $notice->calendarDate->context_id !== $actor->currentContextId()) {
            return false;
        }

        if ((int) $notice->user_id === (int) $actor->getKey()) {
            return true;
        }

        return $notice->is_public && $actor->role->atLeast(Role::Admin);
    }

    public function delete(User $actor, Notice $notice): bool
    {
        return $this->update($actor, $notice);
    }
}
