<?php

use App\Enums\Feature;
use App\Enums\Role;
use App\Models\Context;
use App\Models\Reply;
use App\Models\Thread;
use App\Models\User;

function elsewhereThread(array $attributes = []): Thread
{
    $context = Context::query()->create(['name' => 'Grannarna']);

    return Thread::factory()->create(['context_id' => $context->id, 'body' => 'Grannens tråd', ...$attributes]);
}

it('lists only the household\'s own threads, on the threads page and the start page', function () {
    $user = User::factory()->create();
    Thread::factory()->create(['context_id' => $user->currentContextId(), 'body' => 'Vår tråd']);
    elsewhereThread();
    elsewhereThread(['body' => 'Grannens arkiv', 'archived_at' => now()]);

    $this->actingAs($user)
        ->get(route('threads.index'))
        ->assertSuccessful()
        ->assertSee('Vår tråd')
        ->assertDontSee('Grannens tråd');

    $this->actingAs($user)
        ->get(route('threads.index', ['archived' => 1]))
        ->assertSuccessful()
        ->assertDontSee('Grannens arkiv');

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Vår tråd')
        ->assertDontSee('Grannens tråd');
});

it('refuses to open, reply to, rename, archive or delete another household\'s thread', function () {
    $admin = User::factory()->withRole(Role::Admin)->withFeatures(Feature::ReplyInThreads)->create();
    $thread = elsewhereThread();
    $reply = Reply::factory()->for($thread)->create(['body' => 'Grannens svar']);

    $this->actingAs($admin)->get(route('threads.show', $thread))->assertForbidden();
    $this->actingAs($admin)->post(route('threads.replies.store', $thread), ['body' => 'Hej'])->assertForbidden();
    $this->actingAs($admin)->patch(route('threads.update', $thread), ['title' => 'Fel'])->assertForbidden();
    $this->actingAs($admin)->patch(route('threads.archive', $thread))->assertForbidden();
    $this->actingAs($admin)->delete(route('threads.destroy', $thread))->assertForbidden();
    $this->actingAs($admin)->delete(route('replies.destroy', [$thread, $reply]))->assertForbidden();

    $thread->refresh();

    expect($thread->title)->toBeNull()
        ->and($thread->archived_at)->toBeNull()
        ->and($thread->replies()->count())->toBe(1);
});

it('still lets the household open and manage its own threads', function () {
    $admin = User::factory()->withRole(Role::Admin)->create();
    $thread = Thread::factory()->create(['context_id' => $admin->currentContextId()]);

    $this->actingAs($admin)->get(route('threads.show', $thread))->assertSuccessful();
    $this->actingAs($admin)->patch(route('threads.archive', $thread))->assertRedirect();

    expect($thread->refresh()->archived_at)->not->toBeNull();
});
