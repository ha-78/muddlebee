<?php

namespace App\Policies;

use App\Models\Reply;
use App\Models\User;
use App\Policies\Concerns\ManagesOwnedRecord;

class ReplyPolicy
{
    use ManagesOwnedRecord;

    public function delete(User $actor, Reply $reply): bool
    {
        return (int) $reply->thread->context_id === $actor->currentContextId()
            && $this->managesOwnedRecord($actor, $reply->user_id);
    }
}
