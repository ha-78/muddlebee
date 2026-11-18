<?php

namespace App\Services;

use App\Models\PasswordResetLink;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetLinkService
{
    public function __construct(private DeviceRememberService $devices) {}

    public function pendingFor(User $user): ?PasswordResetLink
    {
        return $user->passwordResetLink()->pending()->first();
    }

    /**
     * A new link replaces any earlier one for the same user, so only the latest link works.
     */
    public function create(User $actor, User $subject): PasswordResetLink
    {
        if (! $actor->can('resetPassword', $subject)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $subject): PasswordResetLink {
            $subject->passwordResetLink()->delete();

            $plainToken = Str::random(64);

            return $subject->passwordResetLink()->create([
                'creator_id' => $actor->id,
                'token_hash' => $this->hashToken($plainToken),
                'token' => $plainToken,
                'expires_at' => $this->expiresAt(),
            ]);
        });
    }

    public function delete(User $actor, User $subject): void
    {
        if (! $actor->can('resetPassword', $subject)) {
            throw new AuthorizationException;
        }

        $subject->passwordResetLink()->delete();
    }

    public function findPending(string $plainToken): ?PasswordResetLink
    {
        if (Str::length($plainToken) !== 64) {
            return null;
        }

        return PasswordResetLink::query()
            ->pending()
            ->where('token_hash', $this->hashToken($plainToken))
            ->with('user')
            ->first();
    }

    /**
     * Set the new password and sign the user out everywhere else: other sessions,
     * "remember me" and remembered devices. The link works once.
     */
    public function reset(string $plainToken, string $password): ?User
    {
        if (Str::length($plainToken) !== 64) {
            return null;
        }

        return DB::transaction(function () use ($plainToken, $password): ?User {
            $link = PasswordResetLink::query()
                ->pending()
                ->where('token_hash', $this->hashToken($plainToken))
                ->lockForUpdate()
                ->first();

            $user = $link?->user;

            if ($user === null) {
                return null;
            }

            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            $this->devices->revokeAllForUser($user);

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }

            $link->delete();

            return $user;
        });
    }

    public function pruneExpired(): void
    {
        PasswordResetLink::query()->where('expires_at', '<=', now())->delete();
    }

    private function expiresAt(): CarbonInterface
    {
        return now()->addHours(max(1, (int) config('invites.password_reset_lifetime_hours')));
    }

    private function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
