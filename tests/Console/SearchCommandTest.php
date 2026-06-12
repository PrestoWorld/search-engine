<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Console;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Console\SearchCommand;
use Prestoworld\SearchEngine\SearchManager;
use Witals\Framework\Application;

class SearchCommandTest extends TestCase
{
    protected SearchCommand $command;
    protected SearchManager $searchManager;
    protected Application $app;

    protected function setUp(): void
    {
        $this->searchManager = $this->createMock(SearchManager::class);
        $this->app = $this->createMock(Application::class);
        $this->app->method('make')->willReturn($this->searchManager);
        
        $this->command = new SearchCommand($this->app);
    }

    public function test_command_properties(): void
    {
        $this->assertSame('search:query', $this->command->name);
        $this->assertSame('Search documents in an index', $this->command->description);
    }

    public function test_handle_returns_error_when_missing_arguments(): void
    {
        $result = $this->command->handle([]);
        $this->assertSame(1, $result);
    }

    public function test_handle_returns_error_when_missing_query(): void
    {
        $result = $this->command->handle(['index']);
        $this->assertSame(1, $result);
    }

    public function test_handle_returns_error_when_missing_index(): void
    {
        $result = $this->command->handle(['query']);
        $this->assertSame(1, $result);
    }

    public function test_handle_calls_search_with_correct_parameters(): void
    {
        $this->searchManager->method('search')->willReturn([
            'results' => [
                ['id' => 1, 'document' => ['title' => 'Test']],
            ],
            'found' => 1,
        ]);

        $result = $this->command->handle(['test query', 'test_index']);
        $this->assertSame(0, $result);
    }

    public function test_handle_with_limit_option(): void
    {
        $this->searchManager->expects($this->once())
            ->method('search')
            ->with('test_index', 'test query', ['limit' => 20])
            ->willReturn([]);

        $this->command->handle(['test query', 'test_index', '--limit=20']);
    }

    public function test_handle_with_adapter_option(): void
    {
        $this->searchManager->expects($this->once())
            ->method('switchAdapter')
            ->with('typesense');
        $this->searchManager->method('search')->willReturn([]);

        $this->command->handle(['test query', 'test_index', '--adapter=typesense']);
    }

    public function test_handle_handles_exception(): void
    {
        $this->searchManager->method('search')
            ->willThrowException(new \Exception('Test error'));

        $result = $this->command->handle(['test query', 'test_index']);
        $this->assertSame(1, $result);
    }

    public function test_handle_with_format_option(): void
    {
        $this->searchManager->method('search')->willReturn([
            'results' => [],
            'found' => 0,
        ]);

        $result = $this->command->handle(['test query', 'test_index', '--format=json']);
        $this->assertSame(0, $result);
    }
}
