<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Blueprints;

use Illuminate\Support\Collection;

class FormConfigBlueprint
{
    public function fields(): array
    {
        return [
            [
                'handle' => 'webhooks',
                'field' => [
                    'display' => 'Webhook URLs',
                    'instructions' => 'Submissions of this form are sent to each of these URLs.',
                    'type' => 'list',
                    'validate' => 'required|array',
                    'localizable' => true,
                ],
            ],
            [
                'handle' => 'consent_field',
                'field' => [
                    'display' => 'Consent Field',
                    'instructions' => 'Optional. When set, submissions are only sent if this form field is true.',
                    'type' => 'zapier_form_fields',
                    'validate' => 'nullable|string',
                    'width' => 50,
                    'localizable' => true,
                ],
            ],
        ];
    }

    public function rules(): array
    {
        return ['webhooks.*' => ['url:http,https']];
    }

    public function columns(): array
    {
        return [
            ['field' => 'webhooks', 'label' => 'Webhooks', 'type' => 'count'],
        ];
    }

    public function listingRow(Collection $values): array
    {
        return [
            'webhooks' => count($values->get('webhooks') ?? []),
        ];
    }
}
