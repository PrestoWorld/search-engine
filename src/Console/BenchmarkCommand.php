<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Console;

use Witals\Framework\Console\Command;
use Prestoworld\SearchEngine\SearchManager;

class BenchmarkCommand extends Command
{
    protected string $name = 'search:benchmark';
    protected string $description = 'Benchmark search performance across different adapters';

    protected array $arguments = [
        'index' => 'The name of the index',
        'query' => 'The search query'
    ];

    protected array $options = [
        '--iterations' => 'Number of iterations per adapter (default: 100)',
        '--adapters' => 'Comma-separated list of adapters to test'
    ];

    public function handle(array $args): int
    {
        $searchManager = $this->app->make(SearchManager::class);
        
        $cleanArgs = array_values(array_filter($args, fn($arg) => !str_starts_with($arg, '-')));
        
        $indexName = $cleanArgs[0] ?? null;
        $query = $cleanArgs[1] ?? null;

        if (!$indexName || !$query) {
            $this->error("Index name and query are required");
            return 1;
        }

        $iterations = (int) $this->getOption($args, 'iterations', 100);

        try {
            $this->info("Running search benchmark...");
            $this->info("Query: '{$query}'");
            $this->info("Iterations: {$iterations}");

            $results = $searchManager->benchmark($indexName, $query, $iterations);
            
            $this->displayBenchmarkResults($results);

            return 0;
        } catch (\Exception $e) {
            $this->error("Benchmark failed: " . $e->getMessage());
            return 1;
        }
    }

    private function displayBenchmarkResults(array $results): void
    {
        foreach ($results as $adapter => $data) {
            if (isset($data['error'])) {
                $this->line("Adapter: {$adapter} | ERROR: {$data['error']}");
            } else {
                $total = number_format($data['total_time'] * 1000, 2);
                $avg = number_format($data['average_time'] * 1000, 2);
                $qps = number_format($data['queries_per_second'], 2);
                $this->line("Adapter: {$adapter} | Total: {$total}ms | Avg: {$avg}ms | QPS: {$qps}");
            }
        }
        
        // Find fastest adapter
        $fastest = null;
        $fastestTime = PHP_FLOAT_MAX;
        
        foreach ($results as $adapter => $data) {
            if (!isset($data['error']) && $data['average_time'] < $fastestTime) {
                $fastest = $adapter;
                $fastestTime = $data['average_time'];
            }
        }
        
        if ($fastest) {
            $this->info("Fastest adapter: {$fastest} (" . number_format($fastestTime * 1000, 2) . "ms average)");
        }
    }
}

