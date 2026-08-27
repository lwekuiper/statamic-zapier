<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Lwekuiper\StatamicZapier\Blueprints\FormConfigBlueprint;
use Lwekuiper\StatamicZapier\Facades\AddonConfig;
use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Integration;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form as FormFacade;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint as BlueprintContract;
use Statamic\Forms\Form;
use Statamic\Http\Controllers\CP\CpController;

class FormConfigController extends CpController
{
    public function index(Request $request)
    {
        $this->authorizeConfigure();

        $site = $this->site($request);
        $urlParams = Integration::multisite() ? ['site' => $site] : [];
        $blueprint = app(FormConfigBlueprint::class);

        $forms = FormFacade::all();

        $formConfigs = $forms->map(function ($form) use ($urlParams, $site, $blueprint) {
            $localConfig = FormConfig::find($form->handle(), $site);
            $resolvedValues = FormConfig::findResolved($form->handle(), $site)?->values() ?? collect();
            $hasLocalData = $localConfig !== null && ! $localConfig->data()->isEmpty();

            return array_merge([
                'title' => $form->title(),
                'edit_url' => Integration::route('form-config.edit', ['form' => $form->handle(), ...$urlParams]),
                'delete_url' => $hasLocalData ? Integration::route('form-config.destroy', ['form' => $form->handle(), ...$urlParams]) : null,
                'status' => $resolvedValues->filter()->isNotEmpty() ? 'published' : 'draft',
            ], $blueprint->listingRow($resolvedValues));
        })->values();

        $viewData = [
            'formConfigs' => $formConfigs,
            'columns' => $blueprint->columns(),
        ];

        if (Integration::multisite()) {
            $viewData = array_merge($viewData, [
                'locale' => $site,
                'localizations' => $this->localizations($site, fn ($handle) => Integration::route('index', ['site' => $handle])),
            ]);
        }

        if ($request->wantsJson()) {
            return $viewData;
        }

        if ($forms->isEmpty()) {
            return Inertia::render(Integration::view('Empty'), [
                'createUrl' => cp_route('forms.create'),
            ]);
        }

        return Inertia::render(Integration::view('Index'), [
            'createFormUrl' => cp_route('forms.create'),
            'configureUrl' => Integration::multisite() ? Integration::route('edit') : null,
            'formConfigs' => $viewData['formConfigs'],
            'columns' => $viewData['columns'],
            'localizations' => $viewData['localizations'] ?? [],
            'site' => $viewData['locale'] ?? '',
        ]);
    }

    public function edit(Request $request, Form $form)
    {
        $this->authorizeConfigure();

        $site = $this->site($request);

        $blueprint = $this->getBlueprint();
        $formConfig = FormConfig::find($form->handle(), $site);

        if (! $formConfig && Integration::multisite() && AddonConfig::hasOrigin($site)) {
            $formConfig = FormConfig::make()->form($form)->locale($site);
        }

        $hasOrigin = Integration::multisite() && $formConfig && $formConfig->hasOrigin();

        if ($hasOrigin) {
            $originValues = $formConfig->origin()->values()->all();
            $displayValues = $formConfig->values()->all();

            $fields = $blueprint->fields()->addValues($displayValues)->preProcess();

            [$originValues, $originMeta] = $this->extractFromFields($originValues, $blueprint);
            $localizedFields = $formConfig->data()->keys()->all();
        } else {
            $fields = $blueprint->fields();

            if ($formConfig) {
                $fields = $fields->addValues($formConfig->data()->all());
            }

            $fields = $fields->preProcess();
        }

        $viewData = [
            'title' => $form->title(),
            'action' => Integration::route('form-config.update', ['form' => $form->handle(), 'site' => $site]),
            'deleteUrl' => $formConfig?->deleteUrl(),
            'listingUrl' => Integration::route('index', ['site' => $site]),
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'hasOrigin' => $hasOrigin,
            'originValues' => $originValues ?? null,
            'originMeta' => $originMeta ?? null,
            'localizedFields' => $localizedFields ?? [],
        ];

        if (Integration::multisite()) {
            $viewData = array_merge($viewData, [
                'locale' => $site,
                'localizations' => $this->localizations($site, fn ($handle) => Integration::route('form-config.edit', ['form' => $form->handle(), 'site' => $handle]), withOrigin: true),
                'configureUrl' => Integration::route('edit'),
            ]);
        }

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render(Integration::view('Edit'), [
            'title' => $viewData['title'],
            'action' => $viewData['action'],
            'deleteUrl' => $viewData['deleteUrl'],
            'listingUrl' => $viewData['listingUrl'],
            'blueprint' => $viewData['blueprint'],
            'values' => $viewData['values'],
            'meta' => $viewData['meta'],
            'localizations' => $viewData['localizations'] ?? [],
            'site' => $viewData['locale'] ?? '',
            'hasOrigin' => $viewData['hasOrigin'],
            'originValues' => $viewData['originValues'],
            'originMeta' => $viewData['originMeta'],
            'localizedFields' => $viewData['localizedFields'],
            'configureUrl' => $viewData['configureUrl'] ?? null,
        ]);
    }

    public function update(Request $request, Form $form)
    {
        $this->authorizeConfigure();

        $site = $this->site($request);

        $blueprint = $this->getBlueprint();
        $fields = $blueprint->fields()->addValues($request->all());
        $fields->validate(app(FormConfigBlueprint::class)->rules());

        $values = $fields->process()->values();

        $hasOrigin = Integration::multisite() && AddonConfig::hasOrigin($site);

        if ($hasOrigin) {
            $values = $values->only($request->input('_localized', []));
        }

        $values = $values->all();

        if (! $formConfig = FormConfig::find($form->handle(), $site)) {
            $formConfig = FormConfig::make()->form($form)->locale($site);
        }

        $formConfig->data($values);

        $formConfig->save();

        if (Integration::multisite()) {
            FormConfig::ensureLocalizationsExist($form->handle());
        }

        return response()->json(['message' => __('Configuration saved')]);
    }

    public function destroy(Request $request, Form $form)
    {
        $this->authorizeConfigure();

        $site = $this->site($request);

        if (! $formConfig = FormConfig::find($form->handle(), $site)) {
            return $this->pageNotFound();
        }

        if ($formConfig->hasOrigin()) {
            $formConfig->data(collect())->save();
        } else {
            $formConfig->delete();
        }

        return response('', 204);
    }

    private function extractFromFields(array $values, BlueprintContract $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($values)
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    private function authorizeConfigure(): void
    {
        $user = User::current();

        abort_unless($user->isSuper() || $user->hasPermission('configure forms'), 401);
    }

    private function site(Request $request): string
    {
        return Integration::multisite()
            ? $request->site ?? Site::selected()->handle()
            : Site::default()->handle();
    }

    private function localizations(string $site, callable $url, bool $withOrigin = false): array
    {
        return Site::all()
            ->filter(fn ($localization) => AddonConfig::isEnabled($localization->handle()))
            ->map(fn ($localization) => array_merge([
                'handle' => $localization->handle(),
                'name' => $localization->name(),
                'active' => $localization->handle() === $site,
                'url' => $url($localization->handle()),
            ], $withOrigin ? ['origin' => ! AddonConfig::hasOrigin($localization->handle())] : []))
            ->values()
            ->all();
    }

    private function getBlueprint(): BlueprintContract
    {
        return Blueprint::make()->setContents([
            'tabs' => [
                'general' => [
                    'display' => 'General',
                    'sections' => [
                        ['fields' => app(FormConfigBlueprint::class)->fields()],
                    ],
                ],
            ],
        ]);
    }
}
