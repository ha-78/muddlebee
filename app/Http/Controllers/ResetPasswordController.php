<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Services\PasswordResetLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The page behind a password reset link: choose a new password, then you're logged in.
 */
class ResetPasswordController extends Controller
{
    public function create(string $token, PasswordResetLinkService $links): View
    {
        $link = $links->findPending($token);

        if ($link?->user === null) {
            abort(response()->view('password-reset.invalid', [], 404));
        }

        return view('password-reset.show', [
            'link' => $link,
            'user' => $link->user,
        ]);
    }

    public function store(ResetPasswordRequest $request, string $token, PasswordResetLinkService $links): RedirectResponse
    {
        $user = $links->reset($token, (string) $request->validated('password'));

        if ($user === null) {
            abort(response()->view('password-reset.invalid', [], 404));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('home')->with('status', __('users.password_reset.saved'));
    }
}
