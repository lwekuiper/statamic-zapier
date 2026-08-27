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
    $this->setTestRoles(['test' => ['access cp', 'configure forms']]);
    $this->user = tap(User::make()->assignRole('test'))->save();
    tap(Form::make('contact_us')->title('Contact Us'))->save();
});

afterEach(function () {
    if (file_exists($path = app(AddonConfig::class)->path())) {
        unlink($path);
    }
    app(AddonConfig::class)->fresh();
});

function update(array $payload): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->user)
        ->patchJson(Integration::route('form-config.update', ['form' => 'contact_us']), $payload);
}

it('saves webhook urls against the form', function () {
    update(['webhooks' => ['https://hooks.zapier.com/hooks/catch/1/a/']])->assertSuccessful();

    expect(FormConfig::find('contact_us', 'default')->value('webhooks'))
        ->toBe(['https://hooks.zapier.com/hooks/catch/1/a/']);
});

it('rejects a webhook url that is not http or https', function () {
    update(['webhooks' => ['gopher://127.0.0.1:6379/_FLUSHALL']])->assertUnprocessable();

    expect(FormConfig::find('contact_us', 'default'))->toBeNull();
});

it('rejects a malformed webhook url', function () {
    update(['webhooks' => ['not a url']])->assertUnprocessable();
});

it('requires at least one webhook', function () {
    update(['webhooks' => []])->assertUnprocessable();
});

it('rejects a scalar instead of a list of webhooks', function () {
    update(['webhooks' => 'https://hooks.zapier.com/hooks/catch/1/a/'])->assertUnprocessable();
});

it('rejects a consent field that is not a string', function () {
    update([
        'webhooks' => ['https://hooks.zapier.com/hooks/catch/1/a/'],
        'consent_field' => ['nested' => 'array'],
    ])->assertUnprocessable();
});

it('marks both fields localizable so a site can override them', function () {
    $this->setProEdition();
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);

    $blueprint = $this->actingAs($this->user)
        ->getJson(Integration::route('form-config.edit', ['form' => 'contact_us', 'site' => 'nl']))
        ->assertSuccessful()
        ->json('blueprint');

    $fields = collect($blueprint['tabs'])
        ->flatMap(fn ($tab) => collect($tab['sections'])->flatMap(fn ($section) => $section['fields']))
        ->keyBy('handle');

    expect($fields['webhooks']['localizable'])->toBeTrue();
    expect($fields['consent_field']['localizable'])->toBeTrue();
});

it('stores only the fields an inheriting site actually overrode', function () {
    $this->setProEdition();
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);
    app(AddonConfig::class)->save(collect(['en' => null, 'nl' => 'en']));

    FormConfig::make()->form('contact_us')->locale('en')
        ->data(['webhooks' => ['https://hooks.zapier.com/hooks/catch/1/en/'], 'consent_field' => 'consent'])
        ->save();

    $this->actingAs($this->user)
        ->patchJson(Integration::route('form-config.update', ['form' => 'contact_us', 'site' => 'nl']), [
            'webhooks' => ['https://hooks.zapier.com/hooks/catch/1/nl/'],
            'consent_field' => 'consent',
            '_localized' => ['webhooks'],
        ])
        ->assertSuccessful();

    $nl = FormConfig::find('contact_us', 'nl');

    expect($nl->data()->keys()->all())->toBe(['webhooks']);
    expect($nl->value('consent_field'))->toBe('consent');
});
