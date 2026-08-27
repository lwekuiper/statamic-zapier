<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Http\Controllers;

use Illuminate\Http\Request;
use Lwekuiper\StatamicZapier\Facades\AddonConfig;
use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Integration;
use Statamic\CP\PublishForm;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

class AddonConfigController extends CpController
{
    public function edit()
    {
        if (! Integration::multisite()) {
            return redirect(Integration::route('index'))
                ->with('error', __('Per-site settings require the pro edition and a multi-site installation.'));
        }

        $this->authorizeConfigure();

        $values = [
            'sites' => Site::all()->map(fn ($site) => [
                'name' => $site->name(),
                'handle' => $site->handle(),
                'enabled' => AddonConfig::isEnabled($site->handle()),
                'origin' => AddonConfig::originFor($site->handle()),
            ])->values()->all(),
        ];

        return PublishForm::make($this->blueprint())
            ->title(__('Configure :name', ['name' => Integration::LABEL]))
            ->values($values)
            ->asConfig()
            ->submittingTo(Integration::route('update'));
    }

    public function update(Request $request)
    {
        $this->authorizeConfigure();

        $fields = $this->blueprint()->fields()->addValues($request->all());
        $fields->validate();
        $values = $fields->process()->values()->all();

        $previousSites = AddonConfig::sites()->keys();

        $newSites = collect($values['sites'])
            ->filter(fn ($site) => $site['enabled'])
            ->mapWithKeys(fn ($site) => [$site['handle'] => $site['origin']]);

        AddonConfig::save($newSites);

        $this->syncFormConfigs($previousSites->all(), $newSites->keys()->all());

        return response('', 204);
    }

    private function syncFormConfigs(array $previousSites, array $newSites): void
    {
        $addedSites = array_diff($newSites, $previousSites);
        $removedSites = array_diff($previousSites, $newSites);

        if (! empty($addedSites)) {
            $existingHandles = collect($previousSites)
                ->flatMap(fn ($site) => FormConfig::whereLocale($site)->map->handle())
                ->unique()
                ->all();

            foreach ($addedSites as $site) {
                foreach ($existingHandles as $handle) {
                    if (! FormConfig::find($handle, $site)) {
                        FormConfig::make()->form($handle)->locale($site)->save();
                    }
                }
            }
        }

        foreach ($removedSites as $site) {
            FormConfig::whereLocale($site)->each->delete();
        }
    }

    private function authorizeConfigure(): void
    {
        abort_unless(Integration::multisite(), 404);

        $user = User::current();

        abort_unless($user->isSuper() || $user->hasPermission('configure forms'), 401);
    }

    protected function blueprint()
    {
        return Blueprint::make()->setContents([
            'tabs' => [
                'main' => [
                    'sections' => [
                        [
                            'display' => __('Sites'),
                            'fields' => [
                                [
                                    'handle' => 'sites',
                                    'field' => [
                                        'type' => 'zapier_sites',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
