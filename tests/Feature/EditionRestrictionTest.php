<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Feature;

use Lwekuiper\StatamicZapier\Integration;
use Lwekuiper\StatamicZapier\Tests\FakesRoles;
use Statamic\Facades\Form;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(FakesRoles::class);
uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);

    $this->setTestRoles(['test' => ['access cp', 'access en site', 'access nl site', 'configure forms']]);
    $this->user = tap(User::make()->assignRole('test'))->save();
    $this->form = tap(Form::make('test_form')->title('Test Form'))->save();
});

it('ignores the requested site and omits localizations in the free edition', function () {
    Site::setSelected('nl');

    $this->actingAs($this->user)
        ->getJson(Integration::route('index', ['site' => 'nl']))
        ->assertOk()
        ->assertJsonMissingPath('locale')
        ->assertJsonMissingPath('localizations');
});

it('exposes localizations for enabled sites in the pro edition', function () {
    $this->setProEdition();
    app(\Lwekuiper\StatamicZapier\Data\AddonConfig::class)->save(collect(['en' => null, 'nl' => 'en']));

    $response = $this->actingAs($this->user)
        ->getJson(Integration::route('index', ['site' => 'nl']))
        ->assertOk();

    expect($response->json('locale'))->toBe('nl');
    expect(collect($response->json('localizations'))->pluck('handle')->all())->toBe(['en', 'nl']);

    app(\Lwekuiper\StatamicZapier\Data\AddonConfig::class)->fresh();
});
