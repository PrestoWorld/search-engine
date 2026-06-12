<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\SearchEngineServiceProvider;
use Witals\Framework\Application;

class SearchEngineServiceProviderTest extends TestCase
{
    protected SearchEngineServiceProvider $provider;
    protected Application $app;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->provider = new SearchEngineServiceProvider($this->app);
    }

    public function test_provider_registers_search_manager(): void
    {
        $this->app->expects($this->once())
            ->method('singleton')
            ->with(
                $this->equalTo('Prestoworld\\SearchEngine\\SearchManager'),
                $this->callback(function($callback) {
                    return is_callable($callback);
                })
            );

        $this->provider->register();
    }

    public function test_provider_registers_search_engine(): void
    {
        $this->app->expects($this->atLeastOnce())
            ->method('singleton');

        $this->provider->register();
    }

    public function test_provider_boot_method(): void
    {
        $this->provider->boot();
        $this->addToAssertionCount(1); // Should not throw exception
    }

    public function test_provider_provides(): void
    {
        $provides = $this->provider->provides();
        $this->assertIsArray($provides);
        $this->assertContains('Prestoworld\\SearchEngine\\SearchManager', $provides);
    }
}
