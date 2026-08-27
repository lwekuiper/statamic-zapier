<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Data;

use Illuminate\Support\Collection;
use Lwekuiper\StatamicZapier\Integration;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;

class AddonConfig
{
    protected ?Collection $sites = null;

    public function sites(): Collection
    {
        if ($this->sites !== null) {
            return $this->sites;
        }

        if (! file_exists($this->path())) {
            return $this->sites = $this->defaultSites();
        }

        $data = YAML::file($this->path())->parse();

        return $this->sites = collect($data['sites'] ?? []);
    }

    public function save(Collection $sites): void
    {
        $this->sites = $sites;

        $data = $this->readConfig();
        $data['sites'] = $sites->all();

        $this->writeConfig($data);
    }

    public function originFor(string $site): ?string
    {
        return $this->sites()->get($site);
    }

    public function hasOrigin(string $site): bool
    {
        return $this->originFor($site) !== null;
    }

    public function isEnabled(string $site): bool
    {
        if (! Integration::multisite()) {
            return $site === Site::default()->handle();
        }

        return $this->sites()->has($site);
    }

    public function configFileExists(): bool
    {
        return file_exists($this->path());
    }

    public function path(): string
    {
        return Integration::storeDirectory().'/config.yaml';
    }

    public function fresh(): static
    {
        $this->sites = null;

        return $this;
    }

    protected function defaultSites(): Collection
    {
        return Site::all()->mapWithKeys(fn ($site) => [$site->handle() => null]);
    }

    protected function readConfig(): array
    {
        if (! file_exists($this->path())) {
            return [];
        }

        return YAML::file($this->path())->parse();
    }

    protected function writeConfig(array $data): void
    {
        $directory = dirname($this->path());

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Failed to create directory: {$directory}");
        }

        if (file_put_contents($this->path(), YAML::dump($data)) === false) {
            throw new \RuntimeException("Failed to write config file: {$this->path()}");
        }
    }
}
