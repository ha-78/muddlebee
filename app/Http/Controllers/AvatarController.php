<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function update(UpdateAvatarRequest $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->pickEmoji($request->user(), $request->string('avatar_emoji')->toString());

        return to_route('account')->with('status', __('users.avatar.updated'));
    }

    public function destroy(Request $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->clear($request->user());

        return to_route('account')->with('status', __('users.avatar.cleared'));
    }
}
