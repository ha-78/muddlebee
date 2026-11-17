<?php

use App\Enums\Feature;
use App\Enums\Role;
use App\Models\Context;
use App\Models\ItemList;
use App\Models\ListItem;
use App\Models\User;

function listFor(User $owner, array $attributes = []): ItemList
{
    return ItemList::factory()->for($owner)->create(['context_id' => $owner->currentContextId(), ...$attributes]);
}

it('asks guests to log in before editing lists and items', function () {
    $list = listFor(User::factory()->create());
    $item = ListItem::factory()->for($list)->create();

    $this->patch(route('lists.update', $list), ['list_name' => 'Ny'])->assertRedirect(route('login'));
    $this->patch(route('lists.items.update', $item), ['edit_name' => 'Ny'])->assertRedirect(route('login'));
});

it('lets the creator rename a list', function () {
    $owner = User::factory()->create();
    $list = listFor($owner, ['name' => 'Kläder', 'is_public' => false]);

    $this->actingAs($owner)
        ->from(route('lists.edit'))
        ->patch(route('lists.update', $list), ['list_name' => '  Barnkläder ', 'list_is_public' => '0'])
        ->assertRedirect(route('lists.edit'))
        ->assertSessionHas('status', __('lists.updated'));

    expect($list->refresh()->name)->toBe('Barnkläder')
        ->and($list->is_public)->toBeFalse();
});

it('needs the publish right to share or unshare a list', function () {
    $owner = User::factory()->create();
    $publisher = User::factory()->withFeatures(Feature::PublishLists)->create();
    $private = listFor($owner, ['is_public' => false]);
    $mine = listFor($publisher, ['is_public' => false]);

    $this->actingAs($owner)
        ->patch(route('lists.update', $private), ['list_name' => 'Delad nu', 'list_is_public' => '1'])
        ->assertForbidden();

    expect($private->refresh()->is_public)->toBeFalse();

    $this->actingAs($publisher)
        ->patch(route('lists.update', $mine), ['list_name' => 'Delad nu', 'list_is_public' => '1'])
        ->assertRedirect();

    expect($mine->refresh()->is_public)->toBeTrue();
});

it('lets admins edit any list, but not other members, readers or another household', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $list = listFor($owner, ['name' => 'Kläder']);
    $elsewhere = ItemList::factory()->for($owner)->create(['context_id' => Context::query()->create(['name' => 'Annan'])->id]);

    $this->actingAs($member)->patch(route('lists.update', $list), ['list_name' => 'Fel', 'list_is_public' => '1'])->assertForbidden();
    $this->actingAs($reader)->patch(route('lists.update', $list), ['list_name' => 'Fel', 'list_is_public' => '1'])->assertForbidden();
    $this->actingAs($admin)->patch(route('lists.update', $elsewhere), ['list_name' => 'Fel', 'list_is_public' => '1'])->assertForbidden();

    expect($list->refresh()->name)->toBe('Kläder');

    $this->actingAs($admin)->patch(route('lists.update', $list), ['list_name' => 'Rätt', 'list_is_public' => '1'])->assertRedirect();

    expect($list->refresh()->name)->toBe('Rätt');
});

it('reopens the edit list sheet with what was typed after a failed save', function () {
    $owner = User::factory()->create();
    $list = listFor($owner, ['name' => 'Kläder', 'is_public' => false]);

    $this->actingAs($owner)
        ->from(route('lists.edit'))
        ->patch(route('lists.update', $list), ['form' => 'edit-list', 'list_edit_id' => $list->id, 'list_name' => '   ', 'list_is_public' => '0'])
        ->assertRedirect(route('lists.edit'));

    $this->actingAs($owner)
        ->get(route('lists.edit'))
        ->assertSuccessful()
        ->assertSee('x-data="{ open: true }"', false)
        ->assertSee('id="edit-list-name-error"', false)
        ->assertDontSee('id="name-error"', false);

    expect($list->refresh()->name)->toBe('Kläder');
});

it('shows a pencil on the lists you may edit', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    listFor($owner, ['name' => 'Min']);
    listFor($other, ['name' => 'Annans']);

    $page = $this->actingAs($owner)->get(route('lists.edit'))->assertSuccessful()->assertSee(__('lists.edit_list'));

    expect(substr_count($page->getContent(), "\$dispatch('edit-list'"))->toBe(1);
});

it('lets the list creator change an item, including Bara jag in a shared list', function () {
    $owner = User::factory()->create();
    $list = listFor($owner);
    $item = ListItem::factory()->for($list)->create(['name' => 'Skor', 'description' => 'Gamla', 'estimate' => 3, 'is_public' => true]);

    $this->actingAs($owner)
        ->from(route('lists.index', ['list' => $list->id]))
        ->patch(route('lists.items.update', $item), [
            'edit_name' => ' Vinterskor ',
            'edit_description' => '',
            'edit_estimate' => '5',
            'edit_private' => '1',
        ])
        ->assertRedirect(route('lists.index', ['list' => $list->id]))
        ->assertSessionHas('status', __('lists.item_updated'));

    $item->refresh();

    expect($item->name)->toBe('Vinterskor')
        ->and($item->description)->toBeNull()
        ->and($item->estimate)->toBe(5)
        ->and($item->is_public)->toBeFalse();
});

it('keeps items in a private list private when they are edited', function () {
    $owner = User::factory()->create();
    $list = listFor($owner, ['is_public' => false]);
    $item = ListItem::factory()->for($list)->create(['is_public' => false]);

    $this->actingAs($owner)->patch(route('lists.items.update', $item), ['edit_name' => 'Nytt', 'edit_private' => '0'])->assertRedirect();

    expect($item->refresh()->is_public)->toBeFalse();
});

it('lets an admin change someone else\'s item without touching Bara jag', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $list = listFor($owner);
    $item = ListItem::factory()->for($list)->create(['name' => 'Mjölk', 'is_public' => true]);

    $this->actingAs($admin)->patch(route('lists.items.update', $item), ['edit_name' => 'Havremjölk', 'edit_private' => '1'])->assertRedirect();

    expect($item->refresh()->name)->toBe('Havremjölk')
        ->and($item->is_public)->toBeTrue();
});

it('refuses item edits from other members, readers and on hidden items', function () {
    $owner = User::factory()->create();
    $member = User::factory()->withFeatures(Feature::AddItemsToSharedLists)->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $list = listFor($owner);
    $item = ListItem::factory()->for($list)->create(['name' => 'Mjölk', 'is_public' => true]);
    $hidden = ListItem::factory()->for($list)->create(['name' => 'Present', 'is_public' => false]);
    $admin = User::factory()->withRole(Role::Admin)->create();

    $this->actingAs($member)->patch(route('lists.items.update', $item), ['edit_name' => 'Fel'])->assertForbidden();
    $this->actingAs($reader)->patch(route('lists.items.update', $item), ['edit_name' => 'Fel'])->assertForbidden();
    $this->actingAs($admin)->patch(route('lists.items.update', $hidden), ['edit_name' => 'Fel'])->assertForbidden();

    expect($item->refresh()->name)->toBe('Mjölk')
        ->and($hidden->refresh()->name)->toBe('Present');
});

it('shows a pencil on items you may edit and reopens the sheet after a failed save', function () {
    $owner = User::factory()->create();
    $list = listFor($owner);
    $item = ListItem::factory()->for($list)->create(['name' => 'Skor']);

    $page = $this->actingAs($owner)
        ->get(route('lists.index', ['list' => $list->id]))
        ->assertSuccessful()
        ->assertSee(__('lists.edit_item'));

    expect(substr_count($page->getContent(), "\$dispatch('edit-item'"))->toBe(1);

    $this->actingAs($owner)
        ->from(route('lists.index', ['list' => $list->id]))
        ->patch(route('lists.items.update', $item), ['form' => 'edit-item', 'item_edit_id' => $item->id, 'edit_name' => '', 'edit_estimate' => '1.5'])
        ->assertRedirect(route('lists.index', ['list' => $list->id]));

    $this->actingAs($owner)
        ->get(route('lists.index', ['list' => $list->id]))
        ->assertSuccessful()
        ->assertSee('x-data="{ open: true }"', false)
        ->assertSee('id="edit-item-name-error"', false)
        ->assertSee('id="edit-item-estimate-error"', false)
        ->assertDontSee('id="item_name-error"', false);

    $this->actingAs(User::factory()->withRole(Role::Reader)->create())
        ->get(route('lists.index', ['list' => $list->id]))
        ->assertSuccessful()
        ->assertDontSee("\$dispatch('edit-item'", false);
});
