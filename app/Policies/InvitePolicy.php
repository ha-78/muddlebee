<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Context;
use App\Models\Invite;
use App\Models\User;

/**
 * Admins and gods invite people to their own context. Admins invite members and readers;
 * only gods may invite admins and gods. (Creating and handling contexts stays with gods.)
 */
class InvitePolicy
{
    public function create(User $actor, Context $context, ?Role $role = null): bool
    {
        return $this->manages($actor, $context->id)
            && ($role === null || $this->mayInviteAs($actor, $role));
    }

    public function delete(User $actor, Invite $invite): bool
    {
        return $this->manages($actor, $invite->context_id)
            && $this->mayInviteAs($actor, $invite->role);
    }

    public function mayInviteAs(User $actor, Role $role): bool
    {
        return $actor->role === Role::God || ! $role->atLeast(Role::Admin);
    }

    private function manages(User $actor, int $contextId): bool
    {
        if (! $actor->role->atLeast(Role::Admin)) {
            return false;
        }

        return $actor->memberships()->where('context_id', $contextId)->exists();
    }
}
