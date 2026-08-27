<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\Forms\Form;

class ZapierFormFields extends Fieldtype
{
    protected static $handle = 'zapier_form_fields';

    protected $component = 'zapier_form_fields';

    public function preload(): array
    {
        $form = request()->route('form');

        if (! $form instanceof Form || ! $blueprint = $form->blueprint()) {
            return ['options' => []];
        }

        return [
            'options' => $blueprint->fields()->all()
                ->map(fn (Field $field, string $handle) => ['id' => $handle, 'label' => $field->display()])
                ->values()
                ->all(),
        ];
    }
}
