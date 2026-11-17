<?php

namespace App\Models;

use Database\Factories\ItemListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['context_id', 'user_id', 'name', 'is_public'])]
class ItemList extends Model
{
    /** @use HasFactory<ItemListFactory> */
    use HasFactory;

    protected $table = 'lists';

    protected $attributes = [
        'is_public' => false,
    ];

    /**
     * Lists the user owns in this context, plus public lists there.
     *
     * @param  Builder<ItemList>  $query
     * @return Builder<ItemList>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $contextId = $query->qualifyColumn('context_id');
        $userId = $query->qualifyColumn('user_id');
        $isPublic = $query->qualifyColumn('is_public');

        return $query
            ->where($contextId, $user->currentContextId())
            ->where(function (Builder $query) use ($user, $userId, $isPublic): void {
                $query->where($userId, $user->getKey())
                    ->orWhere($isPublic, true);
            });
    }

    /**
     * @return BelongsTo<Context, $this>
     */
    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ListItem, $this>
     */
    public function items(): HasMany
    {
        // chaperone(): each item knows its list without another query (policies and the edit sheet use it).
        return $this->hasMany(ListItem::class, 'list_id')->chaperone('itemList');
    }

    public function hasEstimatedItem(): bool
    {
        return $this->items->contains(fn (ListItem $item): bool => $item->estimate !== null);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * What the "Ändra lista" sheet needs.
     *
     * @return array{id: int, action: string, name: string, is_public: bool}
     */
    public function toEditor(): array
    {
        return [
            'id' => $this->getKey(),
            'action' => route('lists.update', $this),
            'name' => $this->name,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
