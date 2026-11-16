<?php

namespace App\Http\Controllers;

use App\Services\DeviceRememberService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceRememberController extends Controller
{
    public function updateTheme(Request $request, DeviceRememberService $deviceRememberService): Response
    {
        $request->validate([
            'theme' => ['required', 'string', 'max:32'],
        ]);

        $device = $deviceRememberService->findDeviceFromCookie($request);

        // is() compares the keys loosely: user_id can come back from the database as a string.
        if ($device === null || ! $device->user()->is($request->user())) {
            return response()->noContent();
        }

        $deviceRememberService->updateTheme($device, $request->string('theme')->toString());

        return response()->noContent();
    }
}
