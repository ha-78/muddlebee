<?php

use App\Models\Reply;
use App\Models\Thread;
use App\Models\User;
use App\Support\Avatar;

it('asks guests to log in before changing an avatar', function () {
    $this->put(route('account.avatar.update'), ['avatar_emoji' => '🐝'])
        ->assertRedirect(route('login'));
    $this->delete(route('account.avatar.destroy'))->assertRedirect(route('login'));
});

it('saves the emoji as json on the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('account.avatar.update'), ['avatar_emoji' => '🦊'])
        ->assertRedirect(route('account'))
        ->assertSessionHas('status', __('users.avatar.updated'));

    expect($user->fresh()->avatar)->toBe(['type' => 'emoji', 'emoji' => '🦊'])
        ->and($user->fresh()->avatarChoice()->emoji)->toBe('🦊');
});

it('accepts any single emoji, including skin tones, flags and families', function (string $emoji) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('account.avatar.update'), ['avatar_emoji' => " {$emoji} "])
        ->assertRedirect(route('account'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->avatarChoice()->emoji)->toBe($emoji);
})->with([
    'plain' => ['💩'],
    'skin tone' => ['👍🏽'],
    'flag' => ['🇸🇪'],
    'family' => ['👨‍👩‍👧'],
    'astronaut' => ['🧑‍🚀'],
    'heart' => ['❤️'],
    'keycap' => ['7️⃣'],
]);

it('refuses text and several emoji', function (string $emoji) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('account'))
        ->put(route('account.avatar.update'), ['avatar_emoji' => $emoji])
        ->assertRedirect(route('account'));

    expect($user->fresh()->avatar)->toBeNull();

    $this->get(route('account'))
        ->assertOk()
        ->assertViewHas('errors', fn ($errors): bool => $errors->has('avatar_emoji'));
})->with([
    'letters' => ['LOL'],
    'a digit' => ['7'],
    'two emoji' => ['🐝🦊'],
    'emoji and text' => ['🐝 hej'],
    'empty' => [''],
]);

it('goes back to the first letter', function () {
    $user = User::factory()->create(['name' => 'Wilma', 'avatar' => Avatar::emoji('🐝')]);

    $this->actingAs($user)
        ->delete(route('account.avatar.destroy'))
        ->assertRedirect(route('account'))
        ->assertSessionHas('status', __('users.avatar.cleared'));

    expect($user->fresh()->avatar)->toBeNull();
});

it('shows the picker on the account page, with the letter option only when an emoji is picked', function () {
    $user = User::factory()->create(['name' => 'Wilma']);

    $this->actingAs($user)
        ->get(route('account'))
        ->assertOk()
        ->assertSee(__('users.avatar.heading'))
        ->assertSee('data-avatar="letter"', false)
        ->assertDontSee(__('users.avatar.use_letter'));

    $user->forceFill(['avatar' => Avatar::emoji('🐼')])->save();

    $this->get(route('account'))
        ->assertSee('data-avatar="emoji"', false)
        ->assertSee('🐼')
        ->assertSee(__('users.avatar.use_letter'));
});

it('falls back to the letter for broken avatar data', function (?array $data) {
    expect(Avatar::from($data))->toBeNull();
})->with([
    'nothing' => [null],
    'unknown type' => [['type' => 'dicebear', 'seed' => 'x']],
    'not an emoji' => [['type' => 'emoji', 'emoji' => 'A']],
    'several emoji' => [['type' => 'emoji', 'emoji' => '🐝🐝']],
]);

it('ignores a colour left in older avatar data', function () {
    expect(Avatar::from(['type' => 'emoji', 'emoji' => '🐝', 'color' => 'c'])->emoji)->toBe('🐝');
});

it('shows the emoji avatar on threads and replies', function () {
    $author = User::factory()->create(['avatar' => Avatar::emoji('🦄')]);
    $reader = User::factory()->create();
    $thread = Thread::factory()->for($author)->create(['context_id' => $reader->currentContextId()]);
    Reply::factory()->for($thread)->for($author)->create();

    $this->actingAs($reader)
        ->get(route('threads.show', $thread))
        ->assertOk()
        ->assertSee('🦄');
});
