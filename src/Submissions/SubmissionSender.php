<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Submissions;

use Lwekuiper\StatamicZapier\Data\FormConfig;
use Lwekuiper\StatamicZapier\Jobs\SendWebhook;
use Statamic\Forms\Submission;
use Statamic\Sites\Site;
use Statamic\Support\Arr;

class SubmissionSender
{
    public function send(FormConfig $config, Submission $submission, Site $site): void
    {
        $handle = $submission->form()->handle();
        $payload = $this->payload($submission, $site);

        foreach (array_values(Arr::wrap($config->value('webhooks'))) as $index => $url) {
            config('queue.default') === 'sync'
                ? SendWebhook::dispatchAfterResponse($url, $payload, $handle, $index)
                : SendWebhook::dispatch($url, $payload, $handle, $index);
        }
    }

    private function payload(Submission $submission, Site $site): array
    {
        return array_merge(collect($submission->data())->all(), [
            '_form' => $submission->form()->handle(),
            '_site' => $site->handle(),
            '_submission_id' => (string) $submission->id(),
            '_submitted_at' => $submission->date()->toIso8601String(),
        ]);
    }
}
