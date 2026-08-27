<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Data;

trait PlatformFields
{
    public function hydrate(array $data): array
    {
        if (! isset($data['consent_field']) && isset($data['legacy_consent'])) {
            $data['consent_field'] = $data['legacy_consent'];
        }
        unset($data['legacy_consent']);

        return $data;
    }

    public function webhooks($value = null)
    {
        if (func_num_args() === 0) {
            return $this->value('webhooks') ?? [];
        }

        return $this->set('webhooks', $value);
    }

    public function consentField($value = null)
    {
        if (func_num_args() === 0) {
            return $this->value('consent_field');
        }

        return $this->set('consent_field', $value);
    }
}
