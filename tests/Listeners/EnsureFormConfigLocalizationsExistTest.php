<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Listeners;

use Lwekuiper\StatamicZapier\Data\AddonConfig;
use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Statamic\Facades\Form;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

afterEach(function () {
    if (file_exists($path = app(AddonConfig::class)->path())) {
        unlink($path);
    }
});

it('creates localizations when a form is saved in pro edition', function () {
    $this->setProEdition();

    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);

    $this->partialMock(AddonConfig::class, function ($mock) {
        $mock->shouldReceive('sites')->andReturn(collect(['en' => null, 'nl' => 'en']));
    });

    tap(Form::make('contact_us')->title('Contact Us'))->save();

    expect(FormConfig::find('contact_us', 'en'))->not->toBeNull();
    expect(FormConfig::find('contact_us', 'nl'))->not->toBeNull();
});

it('does not create localizations in the free edition', function () {
    tap(Form::make('contact_us')->title('Contact Us'))->save();

    expect(FormConfig::find('contact_us', 'default'))->toBeNull();
});
