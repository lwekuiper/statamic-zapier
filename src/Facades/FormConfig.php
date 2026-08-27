<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Facades;

use Illuminate\Support\Facades\Facade;
use Lwekuiper\StatamicZapier\Stache\FormConfigRepository;

class FormConfig extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FormConfigRepository::class;
    }
}
