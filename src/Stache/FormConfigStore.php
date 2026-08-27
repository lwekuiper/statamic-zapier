<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Stache;

use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Integration;
use Statamic\Facades\Path;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class FormConfigStore extends BasicStore
{
    protected $storeIndexes = [
        'handle',
        'locale',
    ];

    public function key()
    {
        return Integration::storeKey();
    }

    public function getItemFilter(SplFileInfo $file)
    {
        if ($file->getExtension() !== 'yaml') {
            return false;
        }

        $filename = Str::after(Path::tidy($file->getPathName()), $this->directory);

        if ($filename === 'config.yaml' || Str::endsWith($filename, '/config.yaml')) {
            return false;
        }

        $slashes = substr_count($filename, '/');

        return $slashes === 0 || $slashes === 1;
    }

    public function makeItemFromFile($path, $contents)
    {
        $relative = Str::after($path, $this->directory);
        $handle = Str::before($relative, '.yaml');

        $data = YAML::file($path)->parse($contents);

        $formConfig = FormConfig::make()->initialPath($path);
        $formConfig->data($formConfig->hydrate($data));

        $handle = explode('/', $handle);
        if (count($handle) > 1) {
            $formConfig->form($handle[1])
                ->locale($handle[0]);
        } else {
            $formConfig->form($handle[0])
                ->locale(Site::default()->handle());
        }

        return $formConfig;
    }

    public function getItemKey($item)
    {
        return "{$item->handle()}::{$item->locale()}";
    }
}
