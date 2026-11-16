<?php

use App\Enums\Feature;
use App\Enums\Role;
use App\Models\CalendarDate;
use App\Models\Context;
use App\Models\Notice;
use App\Models\User;

function noticeOn(User $author, string $date, array $attributes = [], ?int $contextId = null): Notice
{
    $day = CalendarDate::query()->firstOrCreate([
        'context_id' => $contextId ?? $author->currentContextId(),
        'date' => $date,
    ]);

    return $day->notices()->create([
        'user_id' => $author->id,
        'body' => 'Tandläkare',
        'is_public' => true,
        ...$attributes,
    ]);
}

beforeEach(function () {
    $this->travelTo('2026-11-15 10:00:00');
});

it('asks guests to log in before notices', function () {
    $notice = noticeOn(User::factory()->create(), '2026-11-16');

    $this->post(route('notices.store'), ['date' => '2026-11-16', 'body' => 'Hej'])->assertRedirect(route('login'));
    $this->patch(route('notices.update', $notice), ['date' => '2026-11-16', 'body' => 'Hej'])->assertRedirect(route('login'));
    $this->delete(route('notices.destroy', $notice))->assertRedirect(route('login'));
});

it('adds a notice to an empty day and creates the day', function () {
    $user = User::factory()->withFeatures(Feature::CreateNotifications)->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('notices.store'), [
            'date' => '2026-11-16',
            'body' => ' Tandläkare – Maja ',
            'time' => '15:30',
        ])
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', __('notices.created'));

    $notice = Notice::query()->sole();

    expect($notice->body)->toBe('Tandläkare – Maja')
        ->and($notice->short_time)->toBe('15:30')
        ->and($notice->is_public)->toBeTrue()
        ->and($notice->user_id)->toBe($user->id)
        ->and($notice->calendarDate->datetime)->toBe('2026-11-16')
        ->and($notice->calendarDate->context_id)->toBe($user->currentContextId());

    $this->actingAs($user)
        ->post(route('notices.store'), ['date' => '2026-11-16', 'body' => 'Sopor'])
        ->assertRedirect();

    expect(CalendarDate::query()->count())->toBe(1)
        ->and(Notice::query()->count())->toBe(2);
});

it('shows notices in their day box, timed ones first', function () {
    $user = User::factory()->create();
    noticeOn($user, '2026-11-15', ['body' => 'Utan tid']);
    noticeOn($user, '2026-11-15', ['body' => 'Sen tid', 'time' => '18:00']);
    noticeOn($user, '2026-11-15', ['body' => 'Tidig tid', 'time' => '08:15']);
    noticeOn($user, '2026-11-20', ['body' => 'Senare dag']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('08:15')
        ->assertSee('Senare dag')
        ->assertSeeInOrder(['Tidig tid', 'Sen tid', 'Utan tid', 'Senare dag']);
});

it('keeps private notices to their author', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    noticeOn($author, '2026-11-16', ['body' => 'Hemlig present', 'is_public' => false]);
    noticeOn($author, '2026-11-16', ['body' => 'Delad notis']);

    $this->actingAs($author)
        ->get(route('home'))
        ->assertSee('Hemlig present')
        ->assertSee(__('notices.private_badge'));

    $this->actingAs($other)
        ->get(route('home'))
        ->assertSee('Delad notis')
        ->assertDontSee('Hemlig present');
});

it('lets only those with the notification permission add notices', function () {
    $member = User::factory()->create();
    $reader = User::factory()->withRole(Role::Reader)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();

    foreach ([$member, $reader] as $user) {
        $this->actingAs($user)
            ->get(route('home'))
            ->assertDontSee('action="'.route('notices.store').'"', false)
            ->assertDontSee("\$dispatch('open-sheet', 'new-notice')", false);

        $this->actingAs($user)
            ->post(route('notices.store'), ['date' => '2026-11-16', 'body' => 'Nej'])
            ->assertForbidden();
    }

    expect(Notice::query()->count())->toBe(0);

    $adminHome = $this->actingAs($admin)
        ->get(route('home'))
        ->assertSee('action="'.route('notices.store').'"', false)
        ->assertSee("\$dispatch('notice-date', '2026-11-16')", false);

    expect($adminHome->getContent())->toMatch('/<input[^>]*id="new-notice-body"[^>]*data-autofocus/');

    $this->actingAs($admin)
        ->post(route('notices.store'), ['date' => '2026-11-16', 'body' => 'Ja'])
        ->assertRedirect();

    expect(Notice::query()->count())->toBe(1);
});

it('validates the date, text and time', function () {
    $user = User::factory()->withFeatures(Feature::CreateNotifications)->create();

    $this->actingAs($user)
        ->post(route('notices.store'), ['date' => '2026-11-14', 'body' => '   ', 'time' => '25:99'])
        ->assertSessionHasErrors(['date', 'body', 'time']);

    $this->actingAs($user)
        ->post(route('notices.store'), ['date' => 'imorgon', 'body' => 'Hej'])
        ->assertSessionHasErrors(['date']);

    $this->actingAs($user)
        ->post(route('notices.store'), ['date' => '2026-11-15', 'body' => str_repeat('a', 256)])
        ->assertSessionHasErrors(['body']);

    expect(Notice::query()->count())->toBe(0)
        ->and(CalendarDate::query()->count())->toBe(0);
});

it('opens the new notice sheet again after a failed save', function () {
    $user = User::factory()->withFeatures(Feature::CreateNotifications)->create();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('notices.store'), ['form' => 'new-notice', 'date' => '2026-11-18', 'body' => ''])
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSee('x-data="{ open: true }"', false)
        ->assertSee('id="new-notice-body-error"', false)
        ->assertDontSee('id="edit-notice-body-error"', false);
});

it('moves a notice to another day and removes the empty old day', function () {
    $user = User::factory()->withFeatures(Feature::CreateNotifications)->create();
    $notice = noticeOn($user, '2026-11-16');
    $oldDay = $notice->calendarDate;

    $this->actingAs($user)
        ->from(route('home'))
        ->patch(route('notices.update', $notice), [
            'date' => '2026-11-18',
            'body' => 'Tandläkare – flyttad',
            'time' => '09:00',
            'private' => '1',
        ])
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', __('notices.updated'));

    $notice->refresh();

    expect($notice->calendarDate->datetime)->toBe('2026-11-18')
        ->and($notice->body)->toBe('Tandläkare – flyttad')
        ->and($notice->short_time)->toBe('09:00')
        ->and($notice->is_public)->toBeFalse()
        ->and(CalendarDate::query()->find($oldDay->id))->toBeNull();
});

it('deletes a notice and the day only when it is left empty', function () {
    $user = User::factory()->withFeatures(Feature::CreateNotifications)->create();
    $first = noticeOn($user, '2026-11-16');
    $second = noticeOn($user, '2026-11-16', ['body' => 'Sopor']);
    $day = $first->calendarDate;

    $this->actingAs($user)
        ->delete(route('notices.destroy', $first))
        ->assertRedirect()
        ->assertSessionHas('status', __('notices.deleted'));

    expect(CalendarDate::query()->find($day->id))->not->toBeNull();

    $this->actingAs($user)->delete(route('notices.destroy', $second))->assertRedirect();

    expect(Notice::query()->count())->toBe(0)
        ->and(CalendarDate::query()->find($day->id))->toBeNull();
});

it('lets the author and admins change shared notices, but only the author a private one', function () {
    $author = User::factory()->withFeatures(Feature::CreateNotifications)->create();
    $member = User::factory()->withFeatures(Feature::CreateNotifications)->create();
    $admin = User::factory()->withRole(Role::Admin)->create();
    $shared = noticeOn($author, '2026-11-16', ['body' => 'Delad']);
    $private = noticeOn($author, '2026-11-16', ['body' => 'Privat', 'is_public' => false]);
    $change = ['date' => '2026-11-16', 'body' => 'Ändrad'];

    $this->actingAs($member)->patch(route('notices.update', $shared), $change)->assertForbidden();
    $this->actingAs($member)->delete(route('notices.destroy', $shared))->assertForbidden();
    $this->actingAs($admin)->patch(route('notices.update', $private), $change)->assertForbidden();
    $this->actingAs($admin)->delete(route('notices.destroy', $private))->assertForbidden();

    $this->actingAs($member)
        ->get(route('home'))
        ->assertSee('Delad')
        ->assertDontSee("\$dispatch('edit-notice'", false);

    $this->actingAs($admin)->patch(route('notices.update', $shared), $change)->assertRedirect();
    $this->actingAs($author)->patch(route('notices.update', $private), $change)->assertRedirect();

    expect($shared->fresh()->body)->toBe('Ändrad')
        ->and($private->fresh()->body)->toBe('Ändrad');

    // The author can open both of their notices (the shared and the private one) for editing.
    $authorHome = $this->actingAs($author)
        ->get(route('home'))
        ->getContent();

    expect(substr_count($authorHome, "\$dispatch('edit-notice'"))->toBe(2);
});

it('keeps notices inside the household', function () {
    $user = User::factory()->withRole(Role::Admin)->create();
    $elsewhere = Context::query()->create(['name' => 'Annan']);
    $outsider = User::factory()->create();
    $foreign = noticeOn($outsider, '2026-11-16', ['body' => 'Grannens notis'], $elsewhere->id);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertDontSee('Grannens notis');

    $this->actingAs($user)
        ->patch(route('notices.update', $foreign), ['date' => '2026-11-16', 'body' => 'Min nu'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('notices.destroy', $foreign))
        ->assertForbidden();
});

it('lists upcoming notices grouped by day on the all notices page', function () {
    $user = User::factory()->withRole(Role::Reader)->create();
    $author = User::factory()->create(['name' => 'Ada']);
    noticeOn($author, '2026-11-20', ['body' => 'Senare dag']);
    noticeOn($author, '2026-11-15', ['body' => 'Utan tid idag']);
    noticeOn($author, '2026-11-15', ['body' => 'Med tid idag', 'time' => '07:30']);
    noticeOn($author, '2026-11-16', ['body' => 'Imorgon-notis']);
    noticeOn($author, '2026-11-10', ['body' => 'Förra veckan']);
    noticeOn($author, '2026-11-18', ['body' => 'Adas hemlis', 'is_public' => false]);
    $before = CalendarDate::query()->count();

    $heading = fn (string $date): string => CalendarDate::from(Carbon\CarbonImmutable::parse($date))->heading;

    $this->actingAs($user)
        ->get(route('notices.index'))
        ->assertSuccessful()
        ->assertSee('data-section="b"', false)
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee(__('notices.all'))
        ->assertSeeInOrder([
            $heading('2026-11-15'), __('calendar.today'), 'Med tid idag', '07:30', 'Utan tid idag',
            $heading('2026-11-16'), __('calendar.tomorrow'), 'Imorgon-notis',
            $heading('2026-11-20'), 'Senare dag', 'Ada',
        ])
        ->assertDontSee('Förra veckan')
        ->assertDontSee('Adas hemlis')
        ->assertDontSee("\$dispatch('edit-notice'", false)
        ->assertDontSee("\$dispatch('open-sheet', 'new-notice')", false);

    expect(CalendarDate::query()->count())->toBe($before);
});

it('lists earlier notices newest first and read-only', function () {
    $author = User::factory()->withFeatures(Feature::CreateNotifications)->create();
    noticeOn($author, '2026-11-03', ['body' => 'Äldst']);
    noticeOn($author, '2026-11-12', ['body' => 'Nyligen']);
    noticeOn($author, '2026-11-17', ['body' => 'Kommer snart']);

    $this->actingAs($author)
        ->get(route('notices.index', ['past' => 1]))
        ->assertSuccessful()
        ->assertSeeInOrder(['Nyligen', 'Äldst'])
        ->assertDontSee('Kommer snart')
        ->assertDontSee("\$dispatch('edit-notice'", false)
        ->assertSee(__('notices.past'));

    $this->actingAs($author)
        ->get(route('notices.index'))
        ->assertSee('Kommer snart')
        ->assertSee("\$dispatch('edit-notice'", false)
        ->assertSee("\$dispatch('open-sheet', 'new-notice')", false)
        ->assertDontSee('Nyligen');
});

it('says when there are no notices and links the page from the start page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notices.index'))
        ->assertSuccessful()
        ->assertSee(__('notices.empty_upcoming'));

    $this->actingAs($user)
        ->get(route('notices.index', ['past' => 1]))
        ->assertSee(__('notices.empty_past'));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSee('href="'.route('notices.index').'"', false)
        ->assertSee(__('notices.all'));
});

it('returns to the all notices page after a change made there', function () {
    $user = User::factory()->withFeatures(Feature::CreateNotifications)->create();
    $notice = noticeOn($user, '2026-11-16');

    $this->actingAs($user)
        ->from(route('notices.index'))
        ->patch(route('notices.update', $notice), ['date' => '2026-11-17', 'body' => 'Flyttad'])
        ->assertRedirect(route('notices.index'));
});
