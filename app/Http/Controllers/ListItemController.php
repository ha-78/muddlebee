<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteListItemRequest;
use App\Http\Requests\HandleListItemRequest;
use App\Http\Requests\StoreListItemRequest;
use App\Http\Requests\UnhandleListItemRequest;
use App\Http\Requests\UpdateListItemRequest;
use App\Models\ListItem;
use App\Services\ListItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ListItemController extends Controller
{
    public function store(StoreListItemRequest $request, ListItemService $items): RedirectResponse
    {
        $item = $items->create(
            $request->user(),
            $request->list(),
            $request->validated('item_name'),
            $request->validated('item_description'),
            $request->estimate(),
            (bool) $request->validated('item_private'),
        );

        return to_route('lists.index', ['list' => $item->list_id])->with('status', __('lists.item_created'));
    }

    public function handle(HandleListItemRequest $request, ListItem $item, ListItemService $items): Response
    {
        $items->handle($request->user(), $item);

        return response()->noContent();
    }

    public function unhandle(UnhandleListItemRequest $request, ListItem $item, ListItemService $items): Response
    {
        $items->unhandle($request->user(), $item);

        return response()->noContent();
    }

    public function update(UpdateListItemRequest $request, ListItem $item, ListItemService $items): RedirectResponse
    {
        $items->update(
            $request->user(),
            $item,
            $request->validated('edit_name'),
            $request->validated('edit_description'),
            $request->estimate(),
            (bool) $request->validated('edit_private'),
        );

        return back(fallback: route('lists.index', ['list' => $item->list_id]))->with('status', __('lists.item_updated'));
    }

    public function destroy(DeleteListItemRequest $request, ListItem $item, ListItemService $items): RedirectResponse
    {
        $listId = $item->list_id;
        $items->delete($request->user(), $item);

        return to_route('lists.index', ['list' => $listId])->with('status', __('lists.item_deleted'));
    }
}
