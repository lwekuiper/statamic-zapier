<?php

declare(strict_types=1);

namespace Lwekuiper\StatamicZapier\Tests;

use Lwekuiper\StatamicZapier\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_boots_the_addon()
    {
        $this->assertNotNull($this->app->getProvider(ServiceProvider::class));
    }
}
