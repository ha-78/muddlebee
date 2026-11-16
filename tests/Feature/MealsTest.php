<?php

use App\Enums\Feature;
use App\Enums\MealStatus;
use App\Enums\Role;
use App\Models\Context;
use App\Models\Meal;
use App\Models\User;

it('asks guests to log in before meals', function () {
    $this->get(route('meals.index'))
        ->assertRedirect(route('login'));

    $this->post(route('meals.store'), [
        'name' => 'Pasta',
        'comment' => 'Med citron',
    ])->assertRedirect(route('login'));
});

it('shows the meals page with tabs and the wish card in the meals color', function () {
    $this->actingAs(User::factory()->withFeatures(Feature::AddMealsToWishlist)->create())
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee('data-section="f"', false)
        ->assertSee('data-color="f"', false)
        ->assertSee(__('meals.meals'), false)
        ->assertSee('M13 .5c0-.276-.226-.506-.498-.465', false)
        ->assertSeeInOrder([
            __('meals.tabs.wishlist'),
            __('meals.tabs.shortlist'),
            __('meals.tabs.planned'),
            __('meals.tabs.discarded'),
        ])
        ->assertSee('href="'.route('meals.index', ['status' => 'shortlist']).'"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee(__('meals.new_wish'))
        ->assertSee('placeholder="'.__('meals.prompt').'"', false)
        ->assertSee('action="'.route('meals.store').'"', false)
        ->assertSee(__('meals.more'))
        ->assertSee(__('meals.recipe_link'))
        ->assertSee(__('meals.comment'))
        ->assertDontSee('name="status"', false)
        ->assertDontSee('var(--color-', false)
        ->assertSee(__('meals.empty_by_status.wishlist'));
});

it('lists wishlist meals newest first as rows that open', function () {
    $owner = User::factory()->withFeatures(Feature::AddMealsToWishlist)->create(['name' => 'Ada']);
    $viewer = User::factory()->create();
    $createdAt = now()->timezone(config('app.timezone'))->setTime(15, 45);
    Meal::factory()->for($owner)->create([
        'name' => 'Pasta',
        'recipe_link' => 'https://example.com/pasta',
        'comment' => 'Med citron',
        'status' => MealStatus::Wishlist,
        'created_at' => $createdAt,
    ]);
    Meal::factory()->for($owner)->create([
        'name' => 'Soppa',
        'status' => MealStatus::Shortlist,
    ]);
    Meal::factory()->for($owner)->create([
        'name' => 'Tacos',
        'recipe_link' => null,
        'comment' => null,
        'status' => MealStatus::Wishlist,
        'created_at' => $createdAt->copy()->addMinute(),
    ]);

    $response = $this->actingAs($viewer)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee('x-data="{ open: false }"', false)
        ->assertSee('x-bind:aria-expanded="open.toString()"', false)
        ->assertSee('x-show="open"', false)
        ->assertSee('m19.5 8.25-7.5 7.5-7.5-7.5', false)
        ->assertSee('Pasta')
        ->assertSee('Tacos')
        ->assertSee('href="https://example.com/pasta"', false)
        ->assertSee('Med citron')
        ->assertSee(__('meals.no_details'))
        ->assertSee($createdAt->toDateString())
        ->assertSee('Ada')
        ->assertDontSee('Soppa')
        ->assertDontSee(__('meals.empty_by_status.wishlist'));

    expect($response->getContent())->toMatch('/Tacos.*Pasta/s');
});

it('shows each status on its own tab without the wish card', function () {
    $user = User::factory()->withFeatures(Feature::AddMealsToWishlist)->create();
    Meal::factory()->for($user)->create(['name' => 'Önskad', 'status' => MealStatus::Wishlist]);
    Meal::factory()->for($user)->create(['name' => 'Kortad', 'status' => MealStatus::Shortlist]);
    Meal::factory()->for($user)->create(['name' => 'Slängd soppa', 'status' => MealStatus::Discarded]);

    $shortlist = $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'shortlist']))
        ->assertSuccessful()
        ->assertSee('Kortad')
        ->assertDontSee('Önskad')
        ->assertDontSee('action="'.route('meals.store').'"', false);

    expect($shortlist->getContent())
        ->toMatch('/href="'.preg_quote(route('meals.index', ['status' => 'shortlist']), '/').'"\\s+aria-current="page"/');

    $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'planned']))
        ->assertSuccessful()
        ->assertSee(__('meals.nothing_planned'))
        ->assertDontSee('Kortad');

    $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'discarded']))
        ->assertSuccessful()
        ->assertSee('Slängd soppa')
        ->assertDontSee('Önskad')
        ->assertDontSee('action="'.route('meals.store').'"', false);

    $this->actingAs($user)
        ->get(route('meals.index', ['status' => 'cooked']))
        ->assertSuccessful()
        ->assertSee('Önskad')
        ->assertDontSee('Slängd soppa')
        ->assertSee('action="'.route('meals.store').'"', false);
});

it('saves a new meal on the wishlist', function () {
    $user = User::factory()->withFeatures(Feature::AddMealsToWishlist)->create();

    $this->actingAs($user)
        ->post(route('meals.store'), [
            'name' => ' Pasta ',
            'recipe_link' => ' https://example.com/pasta ',
            'comment' => ' Med citron ',
        ])
        ->assertRedirect(route('meals.index'))
        ->assertSessionHas('status', __('meals.created'));

    $meal = Meal::query()->sole();

    expect($meal->name)->toBe('Pasta')
        ->and($meal->recipe_link)->toBe('https://example.com/pasta')
        ->and($meal->comment)->toBe('Med citron')
        ->and($meal->status)->toBe(MealStatus::Wishlist)
        ->and($meal->user_id)->toBe($user->id)
        ->and($meal->context_id)->toBe($user->currentContextId());

    $this->actingAs($user)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee(__('meals.created'));
});

it('keeps a posted meal on the wishlist', function () {
    $user = User::factory()->withFeatures(Feature::AddMealsToWishlist)->create();

    $this->actingAs($user)
        ->post(route('meals.store'), [
            'name' => 'Soppa',
            'recipe_link' => '   ',
            'comment' => '   ',
            'status' => MealStatus::Shortlist->value,
        ])
        ->assertRedirect(route('meals.index'));

    $meal = Meal::query()->sole();

    expect($meal->recipe_link)->toBeNull()
        ->and($meal->comment)->toBeNull()
        ->and($meal->status)->toBe(MealStatus::Wishlist);
});

it('requires only a name', function () {
    $this->actingAs(User::factory()->withFeatures(Feature::AddMealsToWishlist)->create())
        ->post(route('meals.store'), [
            'name' => '   ',
            'recipe_link' => 'not a url',
            'comment' => '   ',
            'status' => 'cooked',
        ])
        ->assertSessionHasErrors(['name', 'recipe_link'])
        ->assertSessionDoesntHaveErrors(['comment', 'status']);

    expect(Meal::query()->count())->toBe(0);
});

it('keeps meals inside the household', function () {
    $user = User::factory()->create();
    $elsewhere = Context::query()->create(['name' => 'Annan']);

    Meal::factory()->for($user)->create([
        'name' => 'Hemmamat',
        'context_id' => $user->currentContextId(),
    ]);
    Meal::factory()->create([
        'name' => 'Grannens mat',
        'context_id' => $elsewhere->id,
    ]);

    $this->actingAs($user)
        ->get(route('meals.index'))
        ->assertSuccessful()
        ->assertSee('Hemmamat')
        ->assertDontSee('Grannens mat');
});

it('lets only those with the wishlist permission add a meal', function () {
    $member = User::factory()->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();

    foreach ([$member, $reader] as $user) {
        $this->actingAs($user)
            ->get(route('meals.index'))
            ->assertSuccessful()
            ->assertDontSee('action="'.route('meals.store').'"', false);

        $this->actingAs($user)
            ->post(route('meals.store'), ['name' => 'Pasta'])
            ->assertForbidden();
    }

    expect(Meal::query()->count())->toBe(0);

    $this->actingAs($admin)
        ->post(route('meals.store'), ['name' => 'Pasta'])
        ->assertRedirect(route('meals.index'));

    expect(Meal::query()->sole()->context_id)->toBe($admin->currentContextId());
});

it('moves a meal between the wishlist, the shortlist and discarded', function () {
    $user = User::factory()->withFeatures(Feature::AddMealsToShortlist)->create();
    $meal = Meal::factory()->for($user)->create(['name' => 'Lasagne', 'status' => MealStatus::Wishlist]);

    $steps = [
        [MealStatus::Wishlist, MealStatus::Shortlist],
        [MealStatus::Shortlist, MealStatus::Wishlist],
        [MealStatus::Wishlist, MealStatus::Discarded],
        [MealStatus::Discarded, MealStatus::Wishlist],
        [MealStatus::Wishlist, MealStatus::Shortlist],
        [MealStatus::Shortlist, MealStatus::Discarded],
    ];

    foreach ($steps as [$from, $to]) {
        expect($meal->fresh()->status)->toBe($from);

        $this->actingAs($user)
            ->patch(route('meals.status', $meal), ['status' => $to->value])
            ->assertRedirect(route('meals.index', ['status' => $from->value]))
            ->assertSessionHas('status', __('meals.moved.'.$to->value, ['name' => 'Lasagne']));

        expect($meal->fresh()->status)->toBe($to);
    }
});

it('refuses moves that are not allowed', function () {
    $user = User::factory()->withFeatures(Feature::AddMealsToShortlist)->create();
    $discarded = Meal::factory()->for($user)->create(['status' => MealStatus::Discarded]);
    $planned = Meal::factory()->for($user)->create(['status' => MealStatus::Planned]);
    $wish = Meal::factory()->for($user)->create(['status' => MealStatus::Wishlist]);

    $this->actingAs($user)
        ->patch(route('meals.status', $discarded), ['status' => MealStatus::Shortlist->value])
        ->assertSessionHasErrors(['status' => __('meals.move_refused')]);

    $this->actingAs($user)
        ->patch(route('meals.status', $planned), ['status' => MealStatus::Wishlist->value])
        ->assertSessionHasErrors(['status' => __('meals.move_refused')]);

    $this->actingAs($user)
        ->patch(route('meals.status', $wish), ['status' => MealStatus::Planned->value])
        ->assertSessionHasErrors(['status' => __('meals.move_refused')]);

    $this->actingAs($user)
        ->patch(route('meals.status', $wish), ['status' => 'cooked'])
        ->assertSessionHasErrors(['status']);

    expect($discarded->fresh()->status)->toBe(MealStatus::Discarded)
        ->and($planned->fresh()->status)->toBe(MealStatus::Planned)
        ->and($wish->fresh()->status)->toBe(MealStatus::Wishlist);
});

it('lets only those with the shortlist permission move meals in their household', function () {
    $member = User::factory()->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $mover = User::factory()->withFeatures(Feature::AddMealsToShortlist)->create();
    $elsewhere = Context::query()->create(['name' => 'Annan']);
    $meal = Meal::factory()->create(['status' => MealStatus::Wishlist, 'context_id' => $admin->currentContextId()]);
    $foreign = Meal::factory()->create(['status' => MealStatus::Wishlist, 'context_id' => $elsewhere->id]);

    foreach ([$member, $reader] as $user) {
        $this->actingAs($user)
            ->patch(route('meals.status', $meal), ['status' => MealStatus::Shortlist->value])
            ->assertForbidden();
    }

    $this->actingAs($mover)
        ->patch(route('meals.status', $foreign), ['status' => MealStatus::Shortlist->value])
        ->assertForbidden();

    expect($meal->fresh()->status)->toBe(MealStatus::Wishlist)
        ->and($foreign->fresh()->status)->toBe(MealStatus::Wishlist);

    $this->actingAs($admin)
        ->patch(route('meals.status', $meal), ['status' => MealStatus::Shortlist->value])
        ->assertRedirect();

    expect($meal->fresh()->status)->toBe(MealStatus::Shortlist);
});

it('shows the move buttons only to those who may move meals', function () {
    $mover = User::factory()->withFeatures(Feature::AddMealsToShortlist)->create();
    $member = User::factory()->create();
    Meal::factory()->for($mover)->create(['name' => 'Önskad', 'status' => MealStatus::Wishlist]);
    Meal::factory()->for($mover)->create(['name' => 'Bortvald', 'status' => MealStatus::Discarded]);

    $this->actingAs($mover)
        ->get(route('meals.index'))
        ->assertSee(__('meals.move_to.shortlist'))
        ->assertSee(__('meals.move_to.discarded'))
        ->assertSee('name="status" value="shortlist"', false);

    $this->actingAs($mover)
        ->get(route('meals.index', ['status' => 'discarded']))
        ->assertSee(__('meals.restore'))
        ->assertDontSee(__('meals.move_to.shortlist'));

    $this->actingAs($member)
        ->get(route('meals.index'))
        ->assertSee('Önskad')
        ->assertDontSee(__('meals.move_to.shortlist'))
        ->assertDontSee('action="'.route('meals.status', Meal::query()->where('name', 'Önskad')->sole()).'"', false);
});
