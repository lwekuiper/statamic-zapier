<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Listeners;

use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Integration;
use Statamic\Events\FormSaved;

class EnsureFormConfigLocalizationsExist
{
    public function handle(FormSaved $event): void
    {
        if (! Integration::multisite()) {
            return;
        }

        FormConfig::ensureLocalizationsExist($event->form->handle());
    }
}
