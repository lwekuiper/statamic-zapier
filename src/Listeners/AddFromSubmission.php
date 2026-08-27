<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Listeners;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Lwekuiper\StatamicZapier\Data\FormConfig;
use Lwekuiper\StatamicZapier\Facades\FormConfig as FormConfigFacade;
use Lwekuiper\StatamicZapier\Integration;
use Lwekuiper\StatamicZapier\Submissions\SubmissionSender;
use Statamic\Events\SubmissionCreated;
use Statamic\Facades\Site;
use Statamic\Forms\Submission;
use Statamic\Sites\Site as SitesSite;

class AddFromSubmission
{
    public function handle(SubmissionCreated $event): void
    {
        $submission = $event->submission;
        $site = $this->site();

        $config = FormConfigFacade::findResolved($submission->form()->handle(), $site->handle());

        if (! $config || $config->values()->filter()->isEmpty()) {
            return;
        }

        if (! $this->hasConsent($submission, $config)) {
            return;
        }

        app(SubmissionSender::class)->send($config, $submission, $site);
    }

    private function site(): SitesSite
    {
        return Integration::multisite()
            ? Site::findByUrl(URL::previous()) ?? Site::default()
            : Site::default();
    }

    private function hasConsent(Submission $submission, FormConfig $config): bool
    {
        if (! $field = $config->value('consent_field')) {
            return true;
        }

        if (! is_string($field)) {
            Log::warning(Integration::LABEL.' consent field is malformed, skipping delivery.', [
                'form' => $submission->form()->handle(),
            ]);

            return false;
        }

        return filter_var(
            Arr::get(Arr::wrap($submission->get($field, false)), 0, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
