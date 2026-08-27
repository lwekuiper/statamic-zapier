<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier;

use Lwekuiper\StatamicZapier\Concerns\BootsIntegration;
use Lwekuiper\StatamicZapier\Data\FormConfig;
use Lwekuiper\StatamicZapier\Fieldtypes\ZapierFormFields;
use Lwekuiper\StatamicZapier\Fieldtypes\ZapierSites;
use Lwekuiper\StatamicZapier\Listeners\AddFromSubmission;
use Lwekuiper\StatamicZapier\Listeners\EnsureFormConfigLocalizationsExist;
use Statamic\Events\FormSaved;
use Statamic\Events\SubmissionCreated;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    use BootsIntegration;

    protected $listen = [
        SubmissionCreated::class => [AddFromSubmission::class],
        FormSaved::class => [EnsureFormConfigLocalizationsExist::class],
    ];

    protected $fieldtypes = [
        ZapierFormFields::class,
        ZapierSites::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => ['resources/js/addon.js'],
        'publicDirectory' => 'resources/dist',
        'hotFile' => 'resources/dist/hot',
    ];

    protected function registerIntegration(): void
    {
        $this->registerSerializableClasses([
            FormConfig::class,
        ]);
    }
}
