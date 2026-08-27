<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Feature;

use Lwekuiper\StatamicZapier\Integration;
use Lwekuiper\StatamicZapier\Tests\FakesRoles;
use Statamic\Facades\Form;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(FakesRoles::class);
uses(PreventsSavingStacheItemsToDisk::class);

it('lists forms with their status and the blueprint columns', function () {
    $this->setTestRoles(['test' => ['access cp', 'configure forms']]);
    $user = tap(User::make()->assignRole('test'))->save();
    tap(Form::make('contact_us')->title('Contact Us'))->save();

    $response = $this->actingAs($user)
        ->getJson(Integration::route('index'))
        ->assertSuccessful();

    $config = collect($response->json('formConfigs'))->firstWhere('title', 'Contact Us');

    expect($config['status'])->toBe('draft');
    expect($config['delete_url'])->toBeNull();
    expect($config['edit_url'])->toBe(Integration::route('form-config.edit', ['form' => 'contact_us']));
    expect($config)->toHaveKeys(collect($response->json('columns'))->pluck('field')->all());
});

it('denies the listing without the configure forms permission', function () {
    $this->setTestRoles(['test' => ['access cp']]);
    $user = tap(User::make()->assignRole('test'))->save();

    $this->actingAs($user)
        ->getJson(Integration::route('index'))
        ->assertUnauthorized();
});
