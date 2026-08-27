<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Stache;

use Lwekuiper\StatamicZapier\Data\AddonConfig;
use Lwekuiper\StatamicZapier\Data\FormConfig;
use Lwekuiper\StatamicZapier\Data\FormConfigCollection;
use Lwekuiper\StatamicZapier\Exceptions\FormConfigNotFoundException;
use Lwekuiper\StatamicZapier\Stache\FormConfigRepository;
use Lwekuiper\StatamicZapier\Stache\FormConfigStore;
use Statamic\Stache\Stache;

afterEach(function () {
    if (file_exists($path = app(AddonConfig::class)->path())) {
        unlink($path);
    }
});

function singleSiteRepo(): array
{
    $stache = (new Stache())->sites(['default']);
    app()->instance(Stache::class, $stache);
    $directory = __DIR__.'/__fixtures__/resources/zapier';
    $stache->registerStore((new FormConfigStore())->directory($directory));

    return [new FormConfigRepository($stache), $directory];
}

function multiSiteRepo(): array
{
    $stache = (new Stache())->sites(['en', 'nl']);
    app()->instance(Stache::class, $stache);
    $directory = __DIR__.'/__fixtures__/resources/zapier-multisite';
    $stache->registerStore((new FormConfigStore())->directory($directory));

    return [new FormConfigRepository($stache), $directory];
}

it('gets all form configs with single site', function () {
    [$repo] = singleSiteRepo();

    $formConfigs = $repo->all();

    expect($formConfigs)->toBeInstanceOf(FormConfigCollection::class);
    expect($formConfigs)->toHaveCount(2);
    $this->assertEveryItemIsInstanceOf($formConfigs, FormConfig::class);
    expect($formConfigs->sortBy->path()->values()->map->id()->all())->toBe(['contact_us::default', 'sign_up::default']);
});

it('gets all form configs with multi site', function () {
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/']]);
    [$repo] = multiSiteRepo();

    $ordered = $repo->all()->sortBy->path()->values();

    expect($ordered->map->id()->all())->toBe(['contact_us::en', 'sign_up::en', 'contact_us::nl', 'sign_up::nl']);
});

it('finds by form and site', function () {
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/']]);
    [$repo] = multiSiteRepo();

    expect($repo->find('contact_us', 'nl')->id())->toBe('contact_us::nl');
    expect($repo->find('unknown', 'en'))->toBeNull();
});

it('filters by form handle and by locale', function () {
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/']]);
    [$repo] = multiSiteRepo();

    expect($repo->whereForm('contact_us'))->toHaveCount(2);
    expect($repo->whereLocale('nl'))->toHaveCount(2);
    expect($repo->whereLocale('unknown'))->toHaveCount(0);
});

it('resolves up the origin chain when the site has no config of its own', function () {
    $this->setProEdition();
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/'], 'de' => ['url' => '/de/']]);
    $stache = (new Stache())->sites(['en', 'nl', 'de']);
    app()->instance(Stache::class, $stache);
    $stache->registerStore((new FormConfigStore())->directory(__DIR__.'/__fixtures__/resources/zapier-multisite'));
    $repo = new FormConfigRepository($stache);

    app(AddonConfig::class)->save(collect(['en' => null, 'nl' => 'en', 'de' => 'nl']));

    expect($repo->findResolved('contact_us', 'nl')->id())->toBe('contact_us::nl');
    expect($repo->findResolved('contact_us', 'de')->id())->toBe('contact_us::nl');
    expect($repo->findResolved('unknown', 'de'))->toBeNull();

    app(AddonConfig::class)->fresh();
});

it('stops walking a cyclic origin chain instead of recursing forever', function () {
    $this->setProEdition();
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/'], 'de' => ['url' => '/de/']]);
    $stache = (new Stache())->sites(['en', 'nl', 'de']);
    app()->instance(Stache::class, $stache);
    $stache->registerStore((new FormConfigStore())->directory(__DIR__.'/__fixtures__/resources/zapier'));
    $repo = new FormConfigRepository($stache);

    app(AddonConfig::class)->save(collect(['en' => 'nl', 'nl' => 'en', 'de' => null]));

    expect($repo->findResolved('unknown', 'en'))->toBeNull();

    app(AddonConfig::class)->fresh();
});

it('saves and deletes a form config', function () {
    [$repo, $directory] = singleSiteRepo();
    @unlink($directory.'/new.yaml');

    $formConfig = $repo->make()->form('new')->locale('default');
    $formConfig->consentField('consent');
    $repo->save($formConfig);

    expect($repo->find('new', 'default')->consentField())->toBe('consent');
    expect(file_exists($directory.'/new.yaml'))->toBeTrue();

    $repo->delete($formConfig);

    expect($repo->find('new', 'default'))->toBeNull();
    expect(file_exists($directory.'/new.yaml'))->toBeFalse();
});

it('creates missing localizations for every enabled site', function () {
    $this->setProEdition();
    $this->setSites(['en' => ['url' => '/'], 'nl' => ['url' => '/nl/']]);
    [$repo, $directory] = multiSiteRepo();
    app(AddonConfig::class)->save(collect(['en' => null, 'nl' => 'en']));

    $repo->ensureLocalizationsExist('newsletter');

    expect($repo->find('newsletter', 'en'))->not->toBeNull();
    expect($repo->find('newsletter', 'nl'))->not->toBeNull();

    @unlink($directory.'/en/newsletter.yaml');
    @unlink($directory.'/nl/newsletter.yaml');
    app(AddonConfig::class)->fresh();
});

it('throws when the form config does not exist', function () {
    [$repo] = singleSiteRepo();

    $repo->findOrFail('does-not-exist', 'default');
})->throws(FormConfigNotFoundException::class, 'Form Config [does-not-exist::default] not found');
