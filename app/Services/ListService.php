<?php

namespace App\Services;

use App\Models\ItemList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListService
{
    /**
     * @return Collection<int, ItemList>
     */
    public function forUser(User $user): Collection
    {
        return ItemList::query()
            ->with('user')
            ->visibleTo($user)
            ->active()
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ItemList>
     */
    public function forUserWithItems(User $user): Collection
    {
        $item = new ListItem;

        return $this->forUser($user)->load([
            'items' => function (HasMany $query) use ($user, $item): void {
                $query->visibleTo($user)
                    ->whereNull($item->qualifyColumn('handled_at'))
                    ->orderBy($item->qualifyColumn('name'))
                    ->orderBy($item->qualifyColumn('id'));
            },
        ]);
    }

    public function create(User $owner, string $name, bool $isPublic): ItemList
    {
        if (! $owner->can('create', ItemList::class)) {
            throw new AuthorizationException;
        }

        return $owner->lists()->create([
            'context_id' => $owner->currentContextId(),
            'name' => $name,
            'is_public' => $isPublic,
        ]);
    }

    /**
     * Rename a list and, with the right to publish lists, share or unshare it. Its items keep their own setting.
     */
    public function update(User $actor, ItemList $list, string $name, bool $isPublic): ItemList
    {
        if (! $actor->can('update', $list)) {
            throw new AuthorizationException;
        }

        if ($isPublic !== (bool) $list->is_public && ! $actor->can('publish', ItemList::class)) {
            throw new AuthorizationException;
        }

        $list->update([
            'name' => $name,
            'is_public' => $isPublic,
        ]);

        return $list;
    }

    /**
     * Archived lists the user can see, most recently archived first.
     *
     * @return Collection<int, ItemList>
     */
    public function archivedFor(User $user): Collection
    {
        return ItemList::query()
            ->with('user')
            ->visibleTo($user)
            ->archived()
            ->latest('archived_at')
            ->orderBy('id')
            ->get();
    }

    public function archive(User $actor, ItemList $list): void
    {
        if (! $actor->can('archive', $list)) {
            throw new AuthorizationException;
        }

        $list->update(['archived_at' => now()]);
    }

    public function unarchive(User $actor, ItemList $list): void
    {
        if (! $actor->can('archive', $list)) {
            throw new AuthorizationException;
        }

        $list->update(['archived_at' => null]);
    }

    public function delete(User $actor, ItemList $list): void
    {
        if (! $actor->can('delete', $list)) {
            throw new AuthorizationException;
        }

        $list->delete();
    }
}
