<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Stache;

use Lwekuiper\StatamicZapier\Data\FormConfig;
use Lwekuiper\StatamicZapier\Stache\FormConfigStore;
use Statamic\Facades\Path;
use Symfony\Component\Finder\SplFileInfo;

function store(string $directory): FormConfigStore
{
    return (new FormConfigStore())->directory($directory);
}

it('accepts form config yaml files up to one directory deep and skips config.yaml', function () {
    $directory = Path::tidy(__DIR__.'/__fixtures__/resources/zapier-multisite');
    $store = store($directory);

    $file = fn (string $relative) => new SplFileInfo($directory.'/'.$relative, dirname($relative), $relative);

    expect($store->getItemFilter($file('en/contact_us.yaml')))->toBeTrue();
    expect($store->getItemFilter($file('contact_us.yaml')))->toBeTrue();
    expect($store->getItemFilter($file('config.yaml')))->toBeFalse();
    expect($store->getItemFilter($file('en/config.yaml')))->toBeFalse();
    expect($store->getItemFilter($file('en/nested/contact_us.yaml')))->toBeFalse();
    expect($store->getItemFilter($file('en/contact_us.md')))->toBeFalse();
});

it('makes an item from a localized path', function () {
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/']]);
    $directory = Path::tidy(__DIR__.'/__fixtures__/resources/zapier-multisite');

    $item = store($directory)->makeItemFromFile($directory.'/nl/contact_us.yaml', "consent_field: consent\n");

    expect($item)->toBeInstanceOf(FormConfig::class);
    expect($item->id())->toBe('contact_us::nl');
    expect($item->consentField())->toBe('consent');
});

it('runs the platform hydrate hook on file data', function () {
    $directory = Path::tidy(__DIR__.'/__fixtures__/resources/zapier');

    $item = store($directory)->makeItemFromFile($directory.'/contact_us.yaml', "legacy_consent: consent\n");

    expect($item->consentField())->toBe('consent');
    expect($item->get('legacy_consent'))->toBeNull();
});
