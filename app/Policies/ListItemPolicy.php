<?php

namespace App\Policies;

use App\Enums\Feature;
use App\Models\ItemList;
use App\Models\ListItem;
use App\Models\User;

class ListItemPolicy
{
    public function viewAny(User $actor): bool
    {
        return true;
    }

    public function view(User $actor, ListItem $item): bool
    {
        if (! $actor->can('view', $item->itemList)) {
            return false;
        }

        if ($item->is_public) {
            return true;
        }

        return (int) $actor->getKey() === (int) $item->itemList->user_id;
    }

    /**
     * Adding to your own lists is always allowed; adding to a shared list someone else created needs AddItemsToSharedLists.
     */
    public function create(User $actor, ?ItemList $list = null): bool
    {
        if (! $actor->canWrite()) {
            return false;
        }

        if ($list === null) {
            return true;
        }

        if (! $actor->can('view', $list)) {
            return false;
        }

        return $this->ownsList($actor, $list) || $actor->allows(Feature::AddItemsToSharedLists);
    }

    /**
     * Only the list's creator may keep an item to themselves, and only in a shared list (a private list's items are private anyway).
     */
    public function addPrivate(User $actor, ItemList $list): bool
    {
        return $this->create($actor, $list) && $this->ownsList($actor, $list) && $list->is_public;
    }

    private function ownsList(User $actor, ItemList $list): bool
    {
        return (int) $actor->getKey() === (int) $list->user_id;
    }

    public function handle(User $actor, ListItem $item): bool
    {
        return $actor->canWrite()
            && $item->handled_at === null
            && $actor->can('view', $item);
    }

    public function unhandle(User $actor, ListItem $item): bool
    {
        return $actor->canWrite()
            && $item->handled_at !== null
            && $actor->can('view', $item);
    }

    public function delete(User $actor, ListItem $item): bool
    {
        return $actor->canWrite()
            && (int) $actor->getKey() === (int) $item->itemList->user_id
            && (int) $item->itemList->context_id === $actor->currentContextId();
    }
}
