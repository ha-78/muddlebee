<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordResetLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Admins create and remove links for a new password from a user's page.
 */
class PasswordResetLinkController extends Controller
{
    public function store(Request $request, User $user, PasswordResetLinkService $links): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $links->create($request->user(), $user);

        return to_route('users.show', $user)->with('status', __('users.password_reset.created'));
    }

    public function destroy(Request $request, User $user, PasswordResetLinkService $links): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $links->delete($request->user(), $user);

        return to_route('users.show', $user)->with('status', __('users.password_reset.removed'));
    }
}
