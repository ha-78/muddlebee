<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveListRequest;
use App\Http\Requests\DeleteListRequest;
use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Models\ItemList;
use App\Models\ListItem;
use App\Services\ListItemService;
use App\Services\ListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListController extends Controller
{
    public function index(Request $request, ListService $lists): View
    {
        Gate::authorize('viewAny', ItemList::class);
        Gate::authorize('viewAny', ListItem::class);

        $user = $request->user();
        $visible = $lists->forUserWithItems($user);
        $selected = $visible->firstWhere('id', $request->integer('list')) ?? $visible->first();

        return view('lists.index', [
            'lists' => $visible,
            'selected' => $selected?->id,
            'publicListIds' => $visible->where('is_public', true)->pluck('id')->values()->all(),
            'ownListIds' => $visible->filter(fn (ItemList $list): bool => (int) $list->user_id === (int) $user->getKey())->pluck('id')->values()->all(),
            'addableListIds' => $visible->filter(fn (ItemList $list): bool => $user->can('create', [ListItem::class, $list]))->pluck('id')->values()->all(),
        ]);
    }

    public function show(Request $request, ItemList $list, ListItemService $items): View
    {
        Gate::authorize('view', $list);

        [$handled, $open] = $items->forList($request->user(), $list)
            ->partition(fn (ListItem $item): bool => $item->handled_at !== null);

        return view('lists.show', [
            'list' => $list,
            'open' => $open,
            'handled' => $handled,
        ]);
    }

    public function edit(Request $request, ListService $lists): View
    {
        Gate::authorize('create', ItemList::class);

        $showingArchived = $request->boolean('archived');

        if ($showingArchived) {
            return view('lists.edit', [
                'showingArchived' => true,
                'archived' => $lists->archivedFor($request->user()),
            ]);
        }

        [$shared, $private] = $lists->forUser($request->user())
            ->partition(fn (ItemList $list): bool => $list->is_public);

        return view('lists.edit', [
            'showingArchived' => false,
            'shared' => $shared,
            'private' => $private,
        ]);
    }

    public function store(StoreListRequest $request, ListService $lists): RedirectResponse
    {
        $lists->create(
            $request->user(),
            $request->validated('name'),
            (bool) $request->validated('is_public'),
        );

        return to_route('lists.edit')->with('status', __('lists.created'));
    }

    public function update(UpdateListRequest $request, ItemList $list, ListService $lists): RedirectResponse
    {
        $lists->update($request->user(), $list, $request->validated('list_name'), (bool) $request->validated('list_is_public'));

        return back(fallback: route('lists.edit'))->with('status', __('lists.updated'));
    }

    public function archive(ArchiveListRequest $request, ItemList $list, ListService $lists): RedirectResponse
    {
        $lists->archive($request->user(), $list);

        return back(fallback: route('lists.edit'))->with('status', __('lists.archived'));
    }

    public function unarchive(ArchiveListRequest $request, ItemList $list, ListService $lists): RedirectResponse
    {
        $lists->unarchive($request->user(), $list);

        return back(fallback: route('lists.edit'))->with('status', __('lists.unarchived'));
    }

    public function destroy(DeleteListRequest $request, ItemList $list, ListService $lists): RedirectResponse
    {
        $lists->delete($request->user(), $list);

        return to_route('lists.edit')->with('status', __('lists.deleted'));
    }
}
