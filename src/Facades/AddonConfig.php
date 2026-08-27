<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Facades;

use Illuminate\Support\Facades\Facade;
use Lwekuiper\StatamicZapier\Data\AddonConfig as AddonConfigData;

class AddonConfig extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddonConfigData::class;
    }
}
