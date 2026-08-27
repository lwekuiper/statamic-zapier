<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests;

use Lwekuiper\StatamicZapier\Integration;
use Lwekuiper\StatamicZapier\ServiceProvider;
use Statamic\Facades\Addon;
use Statamic\Facades\Config;
use Statamic\Facades\Site;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setSites(array $sites): void
    {
        Site::setSites($sites);

        Config::set('statamic.system.multisite', Site::hasMultiple());
    }

    protected function setProEdition(): void
    {
        Config::set('statamic.editions.addons.'.Integration::PACKAGE, 'pro');
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $addon = Addon::get(Integration::PACKAGE);

        if ($addon->editions()->isEmpty()) {
            $addon->editions(['free', 'pro']);
        }

        $app['config']->set('statamic.'.Integration::HANDLE.'.store_directory', __DIR__.'/__fixtures__/dev-null');
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.editions.pro', true);
    }

    protected function assertEveryItemIsInstanceOf($items, string $class): void
    {
        $items = collect($items);

        $this->assertSame(
            $items->count(),
            $items->filter(fn ($item) => $item instanceof $class)->count(),
            'Failed asserting that every item is an instance of '.$class
        );
    }
}
