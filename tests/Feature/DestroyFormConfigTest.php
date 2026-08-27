<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Feature;

use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Tests\FakesRoles;
use Statamic\Facades\Form;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(FakesRoles::class);
uses(PreventsSavingStacheItemsToDisk::class);

it('deletes a form config', function () {
    $this->setTestRoles(['test' => ['access cp', 'configure forms']]);
    $user = tap(User::make()->assignRole('test'))->save();
    $form = tap(Form::make('test_form')->title('Test Form'))->save();

    $formConfig = FormConfig::make()->form($form)->locale('default');
    $formConfig->consentField('consent');
    $formConfig->save();

    expect(FormConfig::all())->toHaveCount(1);

    $this->actingAs($user)
        ->delete($formConfig->deleteUrl())
        ->assertNoContent();

    expect(FormConfig::all())->toHaveCount(0);
});

it('returns 404 when there is nothing to delete', function () {
    $this->setTestRoles(['test' => ['access cp', 'configure forms']]);
    $user = tap(User::make()->assignRole('test'))->save();
    $form = tap(Form::make('test_form')->title('Test Form'))->save();

    $this->actingAs($user)
        ->delete(FormConfig::make()->form($form)->locale('default')->deleteUrl())
        ->assertNotFound();
});

it('denies deleting without the configure forms permission', function () {
    $this->setTestRoles(['test' => ['access cp']]);
    $user = tap(User::make()->assignRole('test'))->save();
    $form = tap(Form::make('test_form')->title('Test Form'))->save();

    $this->actingAs($user)
        ->delete(FormConfig::make()->form($form)->locale('default')->deleteUrl())
        ->assertUnauthorized();
});
