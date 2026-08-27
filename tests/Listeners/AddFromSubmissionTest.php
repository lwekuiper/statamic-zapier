<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Listeners;

use Lwekuiper\StatamicZapier\Data\FormConfig as FormConfigData;
use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Listeners\AddFromSubmission;
use Lwekuiper\StatamicZapier\Submissions\SubmissionSender;
use Statamic\Events\SubmissionCreated;
use Statamic\Facades\Form;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    $this->form = tap(Form::make('contact_us')->title('Contact Us'))->save();

    $this->sender = new class () extends SubmissionSender {
        public array $sent = [];

        public function send(FormConfigData $config, \Statamic\Forms\Submission $submission, \Statamic\Sites\Site $site): void
        {
            $this->sent[] = [$config->id(), $submission->get('email'), $site->handle()];
        }
    };

    app()->instance(SubmissionSender::class, $this->sender);
});

function submit(\Statamic\Forms\Form $form, array $data): void
{
    $submission = $form->makeSubmission()->data($data);

    (new AddFromSubmission())->handle(new SubmissionCreated($submission));
}

it('does nothing when the form has no configuration', function () {
    submit($this->form, ['email' => 'a@example.com']);

    expect($this->sender->sent)->toBe([]);
});

it('does nothing when the resolved configuration is empty', function () {
    FormConfig::make()->form($this->form)->locale('default')->save();

    submit($this->form, ['email' => 'a@example.com']);

    expect($this->sender->sent)->toBe([]);
});

it('skips the submission when the consent field is not truthy', function () {
    $config = FormConfig::make()->form($this->form)->locale('default');
    $config->consentField('consent');
    $config->save();

    submit($this->form, ['email' => 'a@example.com', 'consent' => []]);
    submit($this->form, ['email' => 'b@example.com', 'consent' => ['false']]);

    expect($this->sender->sent)->toBe([]);
});

it('hands a consented submission to the sender with the resolved config and site', function () {
    $config = FormConfig::make()->form($this->form)->locale('default');
    $config->consentField('consent');
    $config->save();

    submit($this->form, ['email' => 'a@example.com', 'consent' => ['true']]);

    expect($this->sender->sent)->toBe([['contact_us::default', 'a@example.com', 'default']]);
});

it('sends without a consent field configured', function () {
    $config = FormConfig::make()->form($this->form)->locale('default');
    $config->set('configured', true);
    $config->save();

    submit($this->form, ['email' => 'a@example.com']);

    expect($this->sender->sent)->toHaveCount(1);
});

it('resolves the site from the previous URL in the pro edition', function () {
    $this->setProEdition();
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);

    FormConfig::make()->form($this->form)->locale('en')->set('configured', 'en-config')->save();
    FormConfig::make()->form($this->form)->locale('nl')->set('configured', 'nl-config')->save();

    request()->headers->set('referer', 'http://localhost/nl/contact');

    submit($this->form, ['email' => 'a@example.com']);

    expect($this->sender->sent)->toBe([['contact_us::nl', 'a@example.com', 'nl']]);
});

it('falls back to the default site when the previous URL matches no site in the pro edition', function () {
    $this->setProEdition();
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);

    FormConfig::make()->form($this->form)->locale('en')->set('configured', 'en-config')->save();
    FormConfig::make()->form($this->form)->locale('nl')->set('configured', 'nl-config')->save();

    request()->headers->set('referer', 'http://unknown-host.test/somewhere');

    submit($this->form, ['email' => 'a@example.com']);

    expect($this->sender->sent)->toBe([['contact_us::en', 'a@example.com', 'en']]);
});

it('always uses the default site in the free edition, whatever the previous URL says', function () {
    $this->setSites([
        'en' => ['url' => 'http://localhost/', 'locale' => 'en', 'name' => 'English'],
        'nl' => ['url' => 'http://localhost/nl/', 'locale' => 'nl', 'name' => 'Dutch'],
    ]);

    FormConfig::make()->form($this->form)->locale('en')->set('configured', 'en-config')->save();
    FormConfig::make()->form($this->form)->locale('nl')->set('configured', 'nl-config')->save();

    request()->headers->set('referer', 'http://localhost/nl/contact');

    submit($this->form, ['email' => 'a@example.com']);

    expect($this->sender->sent)->toBe([['contact_us::en', 'a@example.com', 'en']]);
});

it('skips the submission without throwing when the consent field is malformed', function () {
    $config = FormConfig::make()->form($this->form)->locale('default');
    $config->set('consent_field', ['nested' => 'array']);
    $config->save();

    submit($this->form, ['email' => 'a@example.com', 'consent' => ['true']]);

    expect($this->sender->sent)->toBe([]);
});
