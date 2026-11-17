<?php

namespace App\Policies;

use App\Enums\Feature;
use App\Models\Thread;
use App\Models\User;
use App\Policies\Concerns\ManagesOwnedRecord;

class ThreadPolicy
{
    use ManagesOwnedRecord;

    public function viewAny(User $actor): bool
    {
        return true;
    }

    /**
     * Threads belong to one household; everyone in it may read them.
     */
    public function view(User $actor, Thread $thread): bool
    {
        return $this->inContext($actor, $thread);
    }

    public function create(User $actor): bool
    {
        return $actor->canWrite() && $actor->allows(Feature::CreateThreads);
    }

    public function reply(User $actor, Thread $thread): bool
    {
        return $this->inContext($actor, $thread)
            && $actor->canWrite()
            && $actor->allows(Feature::ReplyInThreads);
    }

    public function update(User $actor, Thread $thread): bool
    {
        return $this->inContext($actor, $thread) && $this->managesOwnedRecord($actor, $thread->user_id);
    }

    public function archive(User $actor, Thread $thread): bool
    {
        return $this->inContext($actor, $thread) && $this->managesOwnedRecord($actor, $thread->user_id);
    }

    public function delete(User $actor, Thread $thread): bool
    {
        return $this->inContext($actor, $thread) && $this->managesOwnedRecord($actor, $thread->user_id);
    }

    private function inContext(User $actor, Thread $thread): bool
    {
        return (int) $thread->context_id === $actor->currentContextId();
    }
}
