<?php

use App\Enums\Role;
use App\Models\PasswordResetLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function resetLinkFor(User $user, ?User $creator = null): PasswordResetLink
{
    return PasswordResetLink::factory()->for($user)->create(['creator_id' => ($creator ?? $user)->id]);
}

it('lets admins create links for members, readers and themselves, and gods for anyone', function (Role $actorRole, Role $subjectRole, bool $allowed) {
    $actor = User::factory()->withRole($actorRole)->create();
    $subject = User::factory()->withRole($subjectRole)->create();

    expect($actor->can('resetPassword', $subject))->toBe($allowed)
        ->and($actor->can('resetPassword', $actor))->toBe($actorRole->atLeast(Role::Admin));
})->with([
    'admin, reader' => [Role::Admin, Role::Reader, true],
    'admin, member' => [Role::Admin, Role::Member, true],
    'admin, other admin' => [Role::Admin, Role::Admin, false],
    'admin, god' => [Role::Admin, Role::God, false],
    'god, god' => [Role::God, Role::God, true],
    'god, admin' => [Role::God, Role::Admin, true],
    'member, reader' => [Role::Member, Role::Reader, false],
]);

it('creates a day-long link that admins can copy from the user page', function () {
    $admin = User::factory()->withRole(Role::Admin)->create();
    $member = User::factory()->withRole(Role::Member)->create();

    $this->actingAs($admin)
        ->get(route('users.show', $member))
        ->assertOk()
        ->assertSee(__('users.password_reset.create'))
        ->assertDontSee(__('users.password_reset.remove'));

    $this->post(route('users.password-reset.store', $member))
        ->assertRedirect(route('users.show', $member))
        ->assertSessionHas('status', __('users.password_reset.created'));

    $link = PasswordResetLink::query()->sole();
    $stored = DB::table('password_reset_links')->where('id', $link->id)->first();
    $hours = max(1, (int) config('invites.password_reset_lifetime_hours'));

    expect($link->user_id)->toBe($member->id)
        ->and($link->creator_id)->toBe($admin->id)
        ->and($link->expires_at->between(now()->addHours($hours)->subMinute(), now()->addHours($hours)->addMinute()))->toBeTrue()
        ->and($stored->token_hash)->toBe(hash('sha256', $link->token))
        ->and($stored->token)->not->toBe($link->token);

    $this->get(route('users.show', $member))
        ->assertSee($link->link(), false)
        ->assertSee(__('users.password_reset.replace'))
        ->assertSee(__('users.password_reset.remove'));
});

it('replaces the earlier link when a new one is created', function () {
    $god = User::factory()->withRole(Role::God)->create();
    $member = User::factory()->withRole(Role::Member)->create();
    $old = resetLinkFor($member, $god);

    $this->actingAs($god)->post(route('users.password-reset.store', $member))->assertRedirect();

    $new = PasswordResetLink::query()->sole();

    expect($new->id)->not->toBe($old->id);

    $this->post(route('logout'));

    $this->get(route('password-reset.show', ['token' => $old->token]))->assertNotFound();
    $this->get(route('password-reset.show', ['token' => $new->token]))->assertOk();
});

it('removes a link', function () {
    $admin = User::factory()->withRole(Role::Admin)->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    resetLinkFor($reader, $admin);

    $this->actingAs($admin)
        ->delete(route('users.password-reset.destroy', $reader))
        ->assertRedirect(route('users.show', $reader))
        ->assertSessionHas('status', __('users.password_reset.removed'));

    expect(PasswordResetLink::query()->count())->toBe(0);
});

it('refuses links for people the actor may not reset', function () {
    $otherAdmin = User::factory()->withRole(Role::Admin)->create();
    $god = User::factory()->withRole(Role::God)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $member = User::factory()->withRole(Role::Member)->create();
    $reader = User::factory()->withRole(Role::Reader)->create();

    $this->actingAs($admin);
    $this->post(route('users.password-reset.store', $otherAdmin))->assertForbidden();
    $this->post(route('users.password-reset.store', $god))->assertForbidden();
    $this->get(route('users.show', $god))->assertOk()->assertDontSee(__('users.password_reset.heading'));

    $this->actingAs($member);
    $this->post(route('users.password-reset.store', $reader))->assertForbidden();

    expect(PasswordResetLink::query()->count())->toBe(0);
});

it('lets the person choose a new password once, and signs them out elsewhere', function () {
    $member = User::factory()->withRole(Role::Member)->create([
        'password' => 'old-password-123',
        'remember_token' => 'old-remember-token',
    ]);
    $oldDevice = $member->rememberDevices()->create([
        'token' => hash('sha256', Str::random(64)),
        'device_name' => 'Gamla telefonen',
        'color_theme' => 'default',
        'last_used_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);
    $link = resetLinkFor($member);

    $this->get(route('password-reset.show', ['token' => $link->token]))
        ->assertOk()
        ->assertSee($member->name)
        ->assertSee(__('users.password_reset.choose_heading'));

    $this->post(route('password-reset.store', ['token' => $link->token]), [
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', __('users.password_reset.saved'));

    $member->refresh();

    $this->assertAuthenticatedAs($member);
    expect(Hash::check('new-password-456', $member->password))->toBeTrue()
        ->and($member->remember_token)->not->toBe('old-remember-token')
        ->and(PasswordResetLink::query()->count())->toBe(0);

    // Other devices are forgotten; the browser that set the new password is remembered as a new device.
    $this->assertModelMissing($oldDevice);
    expect($member->rememberDevices()->pluck('device_name')->all())->not->toContain('Gamla telefonen');

    $this->post(route('logout'));

    $this->get(route('password-reset.show', ['token' => $link->token]))
        ->assertNotFound()
        ->assertSee(__('users.password_reset.invalid'));
});

it('keeps the old password when the new one is refused', function () {
    $member = User::factory()->create(['password' => 'old-password-123']);
    $link = resetLinkFor($member);

    $this->post(route('password-reset.store', ['token' => $link->token]), [
        'password' => 'new-password-456',
        'password_confirmation' => 'something-else',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('old-password-123', $member->fresh()->password))->toBeTrue()
        ->and(PasswordResetLink::query()->count())->toBe(1);
    $this->assertGuest();
});

it('refuses expired and unknown links', function () {
    $member = User::factory()->create(['password' => 'old-password-123']);
    $expired = PasswordResetLink::factory()->for($member)->expired()->create(['creator_id' => $member->id]);

    $this->get(route('password-reset.show', ['token' => $expired->token]))->assertNotFound();
    $this->post(route('password-reset.store', ['token' => $expired->token]), [
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertNotFound();
    $this->get(route('password-reset.show', ['token' => Str::random(64)]))->assertNotFound();

    expect(Hash::check('old-password-123', $member->fresh()->password))->toBeTrue();
});
