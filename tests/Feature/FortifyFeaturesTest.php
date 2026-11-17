<?php

use App\Models\User;

it('has no routes for the Fortify features without pages', function (string $method, string $uri) {
    $this->call($method, $uri)->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->call($method, $uri)
        ->assertNotFound();
})->with([
    'forgot password page' => ['GET', '/forgot-password'],
    'send reset link' => ['POST', '/forgot-password'],
    'reset password page' => ['GET', '/reset-password/'.str_repeat('a', 40)],
    'reset password' => ['POST', '/reset-password'],
    'two-factor challenge' => ['GET', '/two-factor-challenge'],
    'enable two-factor' => ['POST', '/user/two-factor-authentication'],
    'two-factor recovery codes' => ['GET', '/user/two-factor-recovery-codes'],
    'update profile' => ['PUT', '/user/profile-information'],
    'update password' => ['PUT', '/user/password'],
    'passkey login options' => ['GET', '/passkeys/login/options'],
    'register a passkey' => ['POST', '/user/passkeys'],
]);

it('still logs in and out', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'password']);

    $this->get(route('login'))->assertSuccessful();

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'password'])
        ->assertRedirect(config('fortify.home'));

    expect(auth()->id())->toBe($user->id);

    $this->post(route('logout'))->assertRedirect();

    $this->assertGuest();
});
