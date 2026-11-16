<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteNoticeRequest;
use App\Http\Requests\StoreNoticeRequest;
use App\Http\Requests\UpdateNoticeRequest;
use App\Models\Notice;
use App\Services\NoticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(Request $request, NoticeService $notices): View
    {
        $past = $request->boolean('past');

        return view('notices.index', [
            'past' => $past,
            'days' => $past ? $notices->past($request->user()) : $notices->upcoming($request->user()),
        ]);
    }

    public function store(StoreNoticeRequest $request, NoticeService $notices): RedirectResponse
    {
        $notices->create(
            $request->user(),
            $request->day(),
            $request->validated('body'),
            $request->validated('time'),
            $request->isPublic(),
        );

        return back(fallback: route('home'))->with('status', __('notices.created'));
    }

    public function update(UpdateNoticeRequest $request, Notice $notice, NoticeService $notices): RedirectResponse
    {
        $notices->update(
            $request->user(),
            $notice,
            $request->day(),
            $request->validated('body'),
            $request->validated('time'),
            $request->isPublic(),
        );

        return back(fallback: route('home'))->with('status', __('notices.updated'));
    }

    public function destroy(DeleteNoticeRequest $request, Notice $notice, NoticeService $notices): RedirectResponse
    {
        $notices->delete($request->user(), $notice);

        return back(fallback: route('home'))->with('status', __('notices.deleted'));
    }
}
