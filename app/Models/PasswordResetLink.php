<?php

namespace App\Models;

use Database\Factories\PasswordResetLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link that lets one user choose a new password. Like an invite, the token is stored
 * encrypted (so the admin can copy the link again) and as a hash (to look it up).
 */
#[Fillable([
    'user_id',
    'creator_id',
    'token_hash',
    'token',
    'expires_at',
])]
#[Hidden(['token', 'token_hash'])]
class PasswordResetLink extends Model
{
    /** @use HasFactory<PasswordResetLinkFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function link(): string
    {
        return route('password-reset.show', ['token' => $this->token]);
    }

    /**
     * @param  Builder<PasswordResetLink>  $query
     * @return Builder<PasswordResetLink>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('expires_at'), '>', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }
}
