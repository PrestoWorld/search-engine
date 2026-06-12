<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Console;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Console\IndexCommand;
use Prestoworld\SearchEngine\SearchManager;
use Witals\Framework\Application;

class IndexCommandTest extends TestCase
{
    protected IndexCommand $command;
    protected SearchManager $searchManager;
    protected Application $app;

    protected function setUp(): void
    {
        $this->searchManager = $this->createMock(SearchManager::class);
        $this->app = $this->createMock(Application::class);
        $this->app->method('make')->willReturn($this->searchManager);
        
        $this->command = new IndexCommand($this->app);
    }

    public function test_command_properties(): void
    {
        $this->assertSame('search:index', $this->command->name);
        $this->assertSame('Index data for search', $this->command->description);
    }

    public function test_handle_returns_error_when_missing_index(): void
    {
        $result = $this->command->handle([]);
        $this->assertSame(1, $result);
    }

    public function test_handle_with_adapter_option(): void
    {
        $this->searchManager->expects($this->once())
            ->method('switchAdapter')
            ->with('typesense');

        $this->searchManager->method('indexExists')->willReturn(false);
        $this->searchManager->method('index')->willReturn(null);

        $this->command->handle(['test_index', '--adapter=typesense']);
    }

    public function test_handle_with_recreate_option(): void
    {
        $this->searchManager->method('indexExists')->willReturn(true);
        $this->searchManager->expects($this->once())
            ->method('deleteIndex')
            ->with('test_index');
        $this->searchManager->method('index')->willReturn(null);

        $this->command->handle(['test_index', '--recreate']);
    }

    public function test_handle_indexes_documents(): void
    {
        $this->searchManager->method('indexExists')->willReturn(false);
        $this->searchManager->expects($this->once())
            ->method('index')
            ->with('test_index', $this->callback(function($documents) {
                return is_array($documents) && count($documents) === 2;
            }));

        $this->command->handle(['test_index']);
    }

    public function test_handle_handles_exception(): void
    {
        $this->searchManager->method('indexExists')
            ->willThrowException(new \Exception('Test error'));

        $result = $this->command->handle(['test_index']);
        $this->assertSame(1, $result);
    }

    public function test_handle_returns_zero_when_no_documents(): void
    {
        $this->searchManager->method('indexExists')->willReturn(false);
        $this->searchManager->method('index')->willReturn(null);

        $result = $this->command->handle(['test_index', '--source=NonExistentClass']);
        $this->assertSame(0, $result);
    }
}
