<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests\Jobs;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lwekuiper\StatamicZapier\Jobs\SendWebhook;
use Lwekuiper\StatamicZapier\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SendWebhookTest extends TestCase
{
    #[Test]
    public function it_posts_the_payload_as_json()
    {
        Http::fake(['hooks.zapier.com/*' => Http::response(['status' => 'success'], 200)]);

        (new SendWebhook('https://hooks.zapier.com/hooks/catch/1/a/', ['email' => 'a@b.c', '_form' => 'contact_us'], 'contact_us'))->handle();

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://hooks.zapier.com/hooks/catch/1/a/'
                && $request->isJson()
                && $request['email'] === 'a@b.c'
                && $request['_form'] === 'contact_us';
        });
    }

    #[Test]
    public function it_logs_a_warning_on_a_failed_response()
    {
        Http::fake(['hooks.zapier.com/*' => Http::response('', 500)]);
        Log::shouldReceive('warning')->once();

        (new SendWebhook('https://hooks.zapier.com/hooks/catch/1/a/', [], 'contact_us'))->handle();
    }

    #[Test]
    public function it_logs_a_warning_and_does_not_throw_on_connection_failure()
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));
        Log::shouldReceive('warning')->once();

        (new SendWebhook('https://hooks.zapier.com/hooks/catch/1/a/', [], 'contact_us'))->handle();
    }

    #[Test]
    public function it_never_logs_the_webhook_url()
    {
        $url = 'https://hooks.zapier.com/hooks/catch/1/secret-token/';

        Http::fake(fn () => throw new ConnectionException("cURL error 7: Failed to connect for {$url}"));

        $logged = [];
        Log::shouldReceive('warning')->once()->andReturnUsing(function ($message, $context) use (&$logged) {
            $logged = [$message, $context];
        });

        (new SendWebhook($url, [], 'contact_us'))->handle();

        $this->assertStringNotContainsString('secret-token', json_encode($logged));
    }
}
