<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Fieldtypes;

use Illuminate\Contracts\Validation\Rule as ValidationRule;
use Statamic\Fields\Fieldtype;

class ZapierSites extends Fieldtype
{
    protected $selectable = false;

    protected static $handle = 'zapier_sites';

    protected $component = 'zapier_sites';

    public function rules(): array
    {
        return [
            $this->cannotAllHaveOriginsRule(),
            $this->originsMustBeEnabledRule(),
            $this->originsCannotFormACycleRule(),
        ];
    }

    private function originsCannotFormACycleRule(): ValidationRule
    {
        return new class () implements ValidationRule {
            public function passes($attribute, $value): bool
            {
                $origins = collect($value)->filter->enabled->keyBy->handle->map->origin;

                foreach ($origins->keys() as $start) {
                    $seen = [];
                    $site = $start;

                    while ($site !== null && $origins->has($site)) {
                        if (in_array($site, $seen, true)) {
                            return false;
                        }

                        $seen[] = $site;
                        $site = $origins->get($site);
                    }
                }

                return true;
            }

            public function message(): string
            {
                return __('Sites cannot inherit from each other in a loop.');
            }
        };
    }

    private function cannotAllHaveOriginsRule(): ValidationRule
    {
        return new class () implements ValidationRule {
            public function passes($attribute, $value): bool
            {
                $enabled = collect($value)->filter->enabled;

                return $enabled->map->origin->filter()->count() !== $enabled->count();
            }

            public function message(): string
            {
                return __('At least one enabled site must not have an origin.');
            }
        };
    }

    private function originsMustBeEnabledRule(): ValidationRule
    {
        return new class () implements ValidationRule {
            public function passes($attribute, $value): bool
            {
                $sites = collect($value)->keyBy->handle->filter->enabled;
                $origins = $sites->map->origin->filter();

                foreach ($origins as $origin) {
                    if (! $sites->has($origin)) {
                        return false;
                    }
                }

                return true;
            }

            public function message(): string
            {
                return __('An origin site must be enabled.');
            }
        };
    }
}
