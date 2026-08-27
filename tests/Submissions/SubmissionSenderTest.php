<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Submissions;

use Illuminate\Support\Facades\Bus;
use Lwekuiper\StatamicZapier\Facades\FormConfig;
use Lwekuiper\StatamicZapier\Jobs\SendWebhook;
use Lwekuiper\StatamicZapier\Submissions\SubmissionSender;
use Statamic\Facades\Form;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Bus::fake();

    $this->form = tap(Form::make('contact_us')->title('Contact Us'))->save();
});

function send(array $webhooks, array $data = ['email' => 'a@example.com']): void
{
    $config = FormConfig::make()->form('contact_us')->locale('default');
    $config->data(['webhooks' => $webhooks]);

    $submission = test()->form->makeSubmission()->data($data);

    app(SubmissionSender::class)->send($config, $submission, Site::default());
}

it('dispatches one job per webhook, numbered for the log', function () {
    send([
        'https://hooks.zapier.com/hooks/catch/1/a/',
        'https://hooks.zapier.com/hooks/catch/1/b/',
    ]);

    Bus::assertDispatchedAfterResponse(SendWebhook::class, 2);

    Bus::assertDispatchedAfterResponse(SendWebhook::class, fn (SendWebhook $job) => $job->url === 'https://hooks.zapier.com/hooks/catch/1/a/' && $job->webhookIndex === 0);

    Bus::assertDispatchedAfterResponse(SendWebhook::class, fn (SendWebhook $job) => $job->url === 'https://hooks.zapier.com/hooks/catch/1/b/' && $job->webhookIndex === 1);
});

it('sends the submission data plus the reserved metadata keys', function () {
    send(['https://hooks.zapier.com/hooks/catch/1/a/'], ['email' => 'a@example.com', 'name' => 'Ada']);

    Bus::assertDispatchedAfterResponse(SendWebhook::class, function (SendWebhook $job) {
        return $job->payload['email'] === 'a@example.com'
            && $job->payload['name'] === 'Ada'
            && $job->payload['_form'] === 'contact_us'
            && $job->payload['_site'] === 'default'
            && array_key_exists('_submission_id', $job->payload)
            && array_key_exists('_submitted_at', $job->payload);
    });
});

it('does not throw when the stored webhooks value is a scalar', function () {
    $config = FormConfig::make()->form('contact_us')->locale('default');
    $config->data(['webhooks' => 'https://hooks.zapier.com/hooks/catch/1/a/']);

    app(SubmissionSender::class)->send($config, $this->form->makeSubmission()->data([]), Site::default());

    Bus::assertDispatchedAfterResponse(SendWebhook::class, 1);
});

it('queues instead of dispatching after the response when a real queue is configured', function () {
    config(['queue.default' => 'redis']);

    send(['https://hooks.zapier.com/hooks/catch/1/a/']);

    Bus::assertDispatched(SendWebhook::class, 1);
});
