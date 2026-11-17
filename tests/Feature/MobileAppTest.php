<?php

use App\Models\User;

it('links the web app manifest on every page', function () {
    $this->get(route('login'))->assertSee('rel="manifest"', false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('<link rel="manifest" href="'.asset('manifest.webmanifest').'">', false);
});

it('describes Muddlebee in the manifest, starting on the start page', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['name'])->toBe('Muddlebee')
        ->and($manifest['start_url'])->toBe('/home')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['icons'][0]['src'])->toBe('/favicon.svg')
        ->and(file_exists(public_path(ltrim($manifest['icons'][0]['src'], '/'))))->toBeTrue();
});

it('keeps form fields at 16 px on phones so iPhone Safari does not zoom in', function () {
    // Outside a request no $errors is shared; the fields need one.
    $this->withViewErrors([]);

    $html = (string) $this->blade('<x-ui.field label="Namn" name="name" /><x-ui.field label="Text" name="body" multiline /><x-ui.field label="Val" name="pick" :options="[1 => \'Ett\']" />');

    expect(substr_count($html, 'text-base text-field-ink sm:text-[15px]'))->toBe(3)
        ->and($html)->not->toMatch('/class="[^"]*(?<!sm:)text-\[15px\] text-field-ink/');

    $quick = (string) $this->blade('<x-ui.quick-add label="Ny" name="item" action="/x" />');

    expect($quick)->toContain('text-base text-field-ink sm:text-[15px]');
});
