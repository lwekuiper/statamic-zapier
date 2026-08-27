<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhook implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public string $url,
        public array $payload,
        public string $formHandle,
        public int $webhookIndex = 0,
    ) {
    }

    public function handle(): void
    {
        $context = ['form' => $this->formHandle, 'webhook' => $this->webhookIndex + 1];

        try {
            $response = Http::connectTimeout(2)
                ->timeout(5)
                ->asJson()
                ->post($this->url, $this->payload);

            if ($response->failed()) {
                Log::warning('Zapier webhook delivery failed.', $context + ['status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Zapier webhook delivery failed.', $context + ['error' => $e::class]);
        }
    }
}
