<?php

namespace App\Services;

use App\Models\ItemList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class ListItemService
{
    /**
     * Add an item to a list. Items in a private list are private. In a shared list they are shared,
     * unless the list's creator asks to keep the item to themselves; anyone else's items are always shared.
     */
    public function create(User $actor, ItemList $list, string $name, ?string $description, ?int $estimate, bool $private = false): ListItem
    {
        if (! $actor->can('create', [ListItem::class, $list])) {
            throw new AuthorizationException;
        }

        $isPublic = (bool) $list->is_public && ! ($private && $actor->can('addPrivate', [ListItem::class, $list]));

        return $list->items()->create([
            'name' => $name,
            'description' => $description,
            'estimate' => $estimate,
            'is_public' => $isPublic,
        ]);
    }

    /**
     * Change an item's name, description and estimate. The list's creator can also switch "Bara jag"
     * in a shared list; in a private list items stay private, and others leave the setting as it is.
     */
    public function update(User $actor, ListItem $item, string $name, ?string $description, ?int $estimate, bool $private): ListItem
    {
        if (! $actor->can('update', $item)) {
            throw new AuthorizationException;
        }

        $list = $item->itemList;
        $isPublic = match (true) {
            ! $list->is_public => false,
            $actor->can('addPrivate', [ListItem::class, $list]) => ! $private,
            default => (bool) $item->is_public,
        };

        $item->update([
            'name' => $name,
            'description' => $description,
            'estimate' => $estimate,
            'is_public' => $isPublic,
        ]);

        return $item;
    }

    /**
     * @return Collection<int, ListItem>
     */
    public function forList(User $user, ItemList $list): Collection
    {
        $item = new ListItem;

        return $list->items()
            ->with('handler')
            ->visibleTo($user)
            ->orderBy($item->qualifyColumn('handled_at'))
            ->orderBy($item->qualifyColumn('name'))
            ->orderBy($item->qualifyColumn('id'))
            ->get();
    }

    public function handle(User $actor, ListItem $item): void
    {
        if (! $actor->can('handle', $item)) {
            throw new AuthorizationException;
        }

        $item->update([
            'handled_at' => now(),
            'handled_by' => $actor->getKey(),
        ]);
    }

    public function unhandle(User $actor, ListItem $item): void
    {
        if (! $actor->can('unhandle', $item)) {
            throw new AuthorizationException;
        }

        $item->update([
            'handled_at' => null,
            'handled_by' => null,
        ]);
    }

    public function delete(User $actor, ListItem $item): void
    {
        if (! $actor->can('delete', $item)) {
            throw new AuthorizationException;
        }

        $item->delete();
    }
}
