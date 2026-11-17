<?php

use App\Enums\Role;
use App\Models\ItemList;
use App\Models\ListItem;
use App\Models\User;

function archivableList(User $owner, array $attributes = []): ItemList
{
    return ItemList::factory()->for($owner)->create(['context_id' => $owner->currentContextId(), ...$attributes]);
}

it('asks guests to log in before archiving', function () {
    $list = archivableList(User::factory()->create());

    $this->patch(route('lists.archive', $list))->assertRedirect(route('login'));
    $this->patch(route('lists.unarchive', $list))->assertRedirect(route('login'));
});

it('lets the creator archive and restore a list from the edit list sheet', function () {
    $owner = User::factory()->create();
    $list = archivableList($owner, ['name' => 'Julklappar']);

    $this->actingAs($owner)
        ->get(route('lists.edit'))
        ->assertSuccessful()
        ->assertSee(__('lists.archive'))
        ->assertSee("\$dispatch('edit-list'", false);

    $this->actingAs($owner)
        ->from(route('lists.edit'))
        ->patch(route('lists.archive', $list))
        ->assertRedirect(route('lists.edit'))
        ->assertSessionHas('status', __('lists.archived'));

    expect($list->refresh()->isArchived())->toBeTrue();

    $this->actingAs($owner)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee(__('lists.archived_notice'))
        ->assertDontSee('x-data="handleItem"', false);

    $this->actingAs($owner)
        ->from(route('lists.edit'))
        ->patch(route('lists.unarchive', $list))
        ->assertRedirect(route('lists.edit'))
        ->assertSessionHas('status', __('lists.unarchived'));

    expect($list->refresh()->isArchived())->toBeFalse();
});

it('has no separate menu on the list page', function () {
    $owner = User::factory()->create();
    $list = archivableList($owner);

    $this->actingAs($owner)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertDontSee("'open-sheet', 'manage-list'", false)
        ->assertDontSee('action="'.route('lists.archive', $list).'"', false);
});

it('moves an archived list off the lists page and onto the Arkiverade tab', function () {
    $owner = User::factory()->create();
    $active = archivableList($owner, ['name' => 'Handla']);
    $archived = archivableList($owner, ['name' => 'Gammal resa', 'archived_at' => now()]);

    $this->actingAs($owner)
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertSee('Handla')
        ->assertDontSee('Gammal resa');

    $this->actingAs($owner)
        ->get(route('lists.edit'))
        ->assertSuccessful()
        ->assertSee('Handla')
        ->assertDontSee('Gammal resa')
        ->assertSee('href="'.e(route('lists.edit', ['archived' => 1])).'"', false)
        ->assertSee(__('lists.new_list'));

    $this->actingAs($owner)
        ->get(route('lists.edit', ['archived' => 1]))
        ->assertSuccessful()
        ->assertSee('Gammal resa')
        ->assertDontSee('Handla')
        ->assertDontSee(__('lists.new_list'))
        ->assertSee('href="'.route('lists.show', $archived).'"', false);

    ItemList::query()->update(['archived_at' => null]);

    $this->actingAs($owner)
        ->get(route('lists.edit', ['archived' => 1]))
        ->assertSuccessful()
        ->assertSee(__('lists.archived_empty'));
});

it('keeps an archived list read-only', function () {
    $owner = User::factory()->create();
    $list = archivableList($owner, ['archived_at' => now()]);
    $open = ListItem::factory()->for($list)->create(['name' => 'Skor']);
    $handled = ListItem::factory()->for($list)->create(['name' => 'Mössa', 'handled_at' => now(), 'handled_by' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('lists.items.store'), ['list_id' => $list->id, 'item_name' => 'Ny'])
        ->assertSessionHasErrors(['list_id' => __('lists.archived_notice')]);

    $this->actingAs($owner)->patch(route('lists.items.handle', $open))->assertForbidden();
    $this->actingAs($owner)->patch(route('lists.items.unhandle', $handled))->assertForbidden();
    $this->actingAs($owner)->patch(route('lists.items.update', $open), ['edit_name' => 'Fel'])->assertForbidden();

    expect(ListItem::query()->count())->toBe(2)
        ->and($open->refresh()->handled_at)->toBeNull()
        ->and($open->name)->toBe('Skor');

    $this->actingAs($owner)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee('Skor')
        ->assertDontSee('x-data="handleItem"', false)
        ->assertDontSee("\$dispatch('edit-item'", false);
});

it('lets admins archive any list, but not other members or readers', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $list = archivableList($owner);

    $this->actingAs($member)->patch(route('lists.archive', $list))->assertForbidden();
    $this->actingAs($reader)->patch(route('lists.archive', $list))->assertForbidden();

    expect($list->refresh()->isArchived())->toBeFalse();

    $this->actingAs($admin)->patch(route('lists.archive', $list))->assertRedirect();

    expect($list->refresh()->isArchived())->toBeTrue();
});
