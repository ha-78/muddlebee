<?php

namespace App\Services;

use App\Models\User;
use App\Support\Avatar;

class AvatarService
{
    public function pickEmoji(User $user, string $emoji): void
    {
        $user->forceFill(['avatar' => Avatar::emoji($emoji)])->save();
    }

    /**
     * Back to the first letter of the name.
     */
    public function clear(User $user): void
    {
        $user->forceFill(['avatar' => null])->save();
    }
}
