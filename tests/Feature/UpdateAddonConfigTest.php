<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Feature;

use Lwekuiper\StatamicZapier\Data\AddonConfig;
use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Integration;
use Lwekuiper\StatamicZapier\Tests\FakesRoles;
use Statamic\Facades\Form;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(FakesRoles::class);
uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    $this->setProEdition();
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);
    $this->setTestRoles(['test' => ['access cp', 'access en site', 'access nl site', 'configure forms']]);
    $this->user = tap(User::make()->assignRole('test'))->save();
});

afterEach(function () {
    if (file_exists($path = app(AddonConfig::class)->path())) {
        unlink($path);
    }
    app(AddonConfig::class)->fresh();
});

it('saves enabled sites with origins and creates form configs for newly enabled sites', function () {
    tap(Form::make('contact_us')->title('Contact Us'))->save();
    app(AddonConfig::class)->save(collect(['en' => null]));
    FormConfig::make()->form('contact_us')->locale('en')->save();

    $this->actingAs($this->user)
        ->patchJson(Integration::route('update'), [
            'sites' => [
                ['handle' => 'en', 'name' => 'English', 'enabled' => true, 'origin' => null],
                ['handle' => 'nl', 'name' => 'Dutch', 'enabled' => true, 'origin' => 'en'],
            ],
        ])
        ->assertNoContent();

    expect(app(AddonConfig::class)->fresh()->sites()->all())->toBe(['en' => null, 'nl' => 'en']);
    expect(FormConfig::find('contact_us', 'nl'))->not->toBeNull();
});

it('deletes the form configs of a site that gets disabled', function () {
    tap(Form::make('contact_us')->title('Contact Us'))->save();
    app(AddonConfig::class)->save(collect(['en' => null, 'nl' => 'en']));
    FormConfig::make()->form('contact_us')->locale('en')->save();
    FormConfig::make()->form('contact_us')->locale('nl')->save();

    $this->actingAs($this->user)
        ->patchJson(Integration::route('update'), [
            'sites' => [
                ['handle' => 'en', 'name' => 'English', 'enabled' => true, 'origin' => null],
                ['handle' => 'nl', 'name' => 'Dutch', 'enabled' => false, 'origin' => null],
            ],
        ])
        ->assertNoContent();

    expect(FormConfig::find('contact_us', 'nl'))->toBeNull();
    expect(FormConfig::find('contact_us', 'en'))->not->toBeNull();
});

it('rejects a configuration where every enabled site has an origin', function () {
    $this->actingAs($this->user)
        ->patchJson(Integration::route('update'), [
            'sites' => [
                ['handle' => 'en', 'name' => 'English', 'enabled' => true, 'origin' => 'nl'],
                ['handle' => 'nl', 'name' => 'Dutch', 'enabled' => true, 'origin' => 'en'],
            ],
        ])
        ->assertUnprocessable();
});

it('rejects a loop that a third root site would otherwise hide', function () {
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
        'es' => ['url' => 'http://localhost/es/', 'locale' => 'es', 'name' => 'Spanish'],
    ]);

    $this->actingAs($this->user)
        ->patchJson(Integration::route('update'), [
            'sites' => [
                ['handle' => 'en', 'name' => 'English', 'enabled' => true, 'origin' => 'nl'],
                ['handle' => 'nl', 'name' => 'Dutch', 'enabled' => true, 'origin' => 'en'],
                ['handle' => 'es', 'name' => 'Spanish', 'enabled' => true, 'origin' => null],
            ],
        ])
        ->assertUnprocessable();
});

it('rejects a longer loop that a root site would otherwise hide', function () {
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
        'es' => ['url' => 'http://localhost/es/', 'locale' => 'es', 'name' => 'Spanish'],
        'de' => ['url' => 'http://localhost/de/', 'locale' => 'de', 'name' => 'German'],
    ]);

    $this->actingAs($this->user)
        ->patchJson(Integration::route('update'), [
            'sites' => [
                ['handle' => 'en', 'name' => 'English', 'enabled' => true, 'origin' => null],
                ['handle' => 'nl', 'name' => 'Dutch', 'enabled' => true, 'origin' => 'es'],
                ['handle' => 'es', 'name' => 'Spanish', 'enabled' => true, 'origin' => 'de'],
                ['handle' => 'de', 'name' => 'German', 'enabled' => true, 'origin' => 'nl'],
            ],
        ])
        ->assertUnprocessable();
});

it('accepts several sites inheriting from one root', function () {
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
        'es' => ['url' => 'http://localhost/es/', 'locale' => 'es', 'name' => 'Spanish'],
        'de' => ['url' => 'http://localhost/de/', 'locale' => 'de', 'name' => 'German'],
    ]);

    $this->actingAs($this->user)
        ->patchJson(Integration::route('update'), [
            'sites' => [
                ['handle' => 'en', 'name' => 'English', 'enabled' => true, 'origin' => null],
                ['handle' => 'nl', 'name' => 'Dutch', 'enabled' => true, 'origin' => 'en'],
                ['handle' => 'es', 'name' => 'Spanish', 'enabled' => true, 'origin' => 'nl'],
                ['handle' => 'de', 'name' => 'German', 'enabled' => true, 'origin' => 'en'],
            ],
        ])
        ->assertSuccessful();
});
