<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Console;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Console\BenchmarkCommand;
use Prestoworld\SearchEngine\SearchManager;
use Witals\Framework\Application;

class BenchmarkCommandTest extends TestCase
{
    protected BenchmarkCommand $command;
    protected SearchManager $searchManager;
    protected Application $app;

    protected function setUp(): void
    {
        $this->searchManager = $this->createMock(SearchManager::class);
        $this->app = $this->createMock(Application::class);
        $this->app->method('make')->willReturn($this->searchManager);
        
        $this->command = new BenchmarkCommand($this->app);
    }

    public function test_command_properties(): void
    {
        $this->assertSame('search:benchmark', $this->command->name);
        $this->assertSame('Benchmark search performance across different adapters', $this->command->description);
    }

    public function test_handle_returns_error_when_missing_arguments(): void
    {
        $result = $this->command->handle([]);
        $this->assertSame(1, $result);
    }

    public function test_handle_returns_error_when_missing_index(): void
    {
        $result = $this->command->handle(['query']);
        $this->assertSame(1, $result);
    }

    public function test_handle_returns_error_when_missing_query(): void
    {
        $result = $this->command->handle(['index']);
        $this->assertSame(1, $result);
    }

    public function test_handle_calls_benchmark_with_correct_parameters(): void
    {
        $this->searchManager->method('benchmark')->willReturn([
            'tntsearch' => [
                'total_time' => 1.5,
                'average_time' => 0.015,
                'queries_per_second' => 66.67,
            ],
        ]);

        $result = $this->command->handle(['test_index', 'test query']);
        $this->assertSame(0, $result);
    }

    public function test_handle_with_iterations_option(): void
    {
        $this->searchManager->expects($this->once())
            ->method('benchmark')
            ->with('test_index', 'test query', 50)
            ->willReturn([]);

        $this->command->handle(['test_index', 'test query', '--iterations=50']);
    }

    public function test_handle_handles_exception(): void
    {
        $this->searchManager->method('benchmark')
            ->willThrowException(new \Exception('Test error'));

        $result = $this->command->handle(['test_index', 'test query']);
        $this->assertSame(1, $result);
    }
}
