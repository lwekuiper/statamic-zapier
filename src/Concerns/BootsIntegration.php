<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Concerns;

use Lwekuiper\StatamicZapier\Data\AddonConfig;
use Lwekuiper\StatamicZapier\Integration;
use Lwekuiper\StatamicZapier\Stache\FormConfigRepository;
use Lwekuiper\StatamicZapier\Stache\FormConfigStore;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Form;
use Statamic\Facades\Site;
use Statamic\Stache\Stache;
use Statamic\Statamic;

trait BootsIntegration
{
    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'statamic.'.Integration::HANDLE);

        $this->app->singleton(FormConfigRepository::class, fn () => new FormConfigRepository($this->app['stache']));
        $this->app->singleton(AddonConfig::class, fn () => new AddonConfig());

        $this->publishes([
            $this->configPath() => config_path('statamic/'.Integration::HANDLE.'.php'),
        ], 'statamic-'.Integration::HANDLE.'-config');

        $this->registerIntegration();
    }

    public function bootAddon()
    {
        Nav::extend(function ($nav) {
            $nav->create(Integration::LABEL)
                ->section('Tools')
                ->url($this->navUrl())
                ->can('index', Form::class)
                ->icon(file_get_contents(__DIR__.'/../../resources/svg/'.Integration::HANDLE.'.svg'))
                ->children(fn () => $this->navChildren());
        });

        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', ['--tag' => 'statamic-'.Integration::HANDLE.'-config']);
        });

        app(Stache::class)->registerStore((new FormConfigStore())->directory(Integration::storeDirectory()));

        $this->bootIntegration();
    }

    protected function registerIntegration(): void
    {
    }

    protected function bootIntegration(): void
    {
    }

    private function configPath(): string
    {
        return __DIR__.'/../../config/'.Integration::HANDLE.'.php';
    }

    private function siteEnabled(): bool
    {
        return app(AddonConfig::class)->isEnabled(Site::selected()->handle());
    }

    private function navUrl(): string
    {
        return Integration::multisite() && ! $this->siteEnabled()
            ? Integration::route('edit')
            : Integration::route('index');
    }

    private function navChildren()
    {
        if (! $this->siteEnabled()) {
            return collect();
        }

        return Form::all()->sortBy->title()->map(fn ($form) => Nav::item($form->title())
            ->url(Integration::route('form-config.edit', ['form' => $form->handle()]))
            ->can('edit', $form));
    }
}
