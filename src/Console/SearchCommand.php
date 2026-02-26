<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Console;

use Witals\Framework\Console\Command;
use Prestoworld\SearchEngine\SearchManager;

class SearchCommand extends Command
{
    protected string $name = 'search:query';
    protected string $description = 'Search documents in an index';

    protected array $arguments = [
        'query' => 'The search query',
        'index' => 'The name of the index'
    ];

    protected array $options = [
        '--limit' => 'Number of results to return (default: 10)',
        '--adapter' => 'Override the default adapter',
        '--format' => 'Output format (json, array)'
    ];

    public function handle(array $args): int
    {
        $searchManager = $this->app->make(SearchManager::class);
        
        // Find query and index in args (excluding options)
        $cleanArgs = array_values(array_filter($args, fn($arg) => !str_starts_with($arg, '-')));
        
        $query = $cleanArgs[0] ?? null;
        $indexName = $cleanArgs[1] ?? null;

        if (!$query || !$indexName) {
            $this->error("Query and index name are required");
            $this->line("Usage: php witals search:query <query> <index>");
            return 1;
        }

        $limit = (int) $this->getOption($args, 'limit', 10);
        $adapter = $this->getOption($args, 'adapter');
        $format = $this->getOption($args, 'format', 'json');

        if ($adapter) {
            $searchManager->switchAdapter($adapter);
            $this->info("Using adapter: {$adapter}");
        }

        try {
            $this->info("Searching for: '{$query}' in index: {$indexName}");
            
            $startTime = microtime(true);
            $results = $searchManager->search($indexName, $query, ['limit' => $limit]);
            $endTime = microtime(true);
            
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $this->displayResults($results, $format, $executionTime);

            return 0;
        } catch (\Exception $e) {
            $this->error("Search failed: " . $e->getMessage());
            return 1;
        }
    }

    private function displayResults(array $results, string $format, float $executionTime): void
    {
        $formattedResults = $results['results'] ?? $results;

        if (empty($formattedResults)) {
            $this->warn("No results found");
            return;
        }

        $this->info("Found " . count($formattedResults) . " results in " . number_format($executionTime, 2) . "ms");

        switch ($format) {
            case 'json':
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
                break;
                
            case 'array':
                $this->line(print_r($results, true));
                break;
                
            default:
                // Simple list output since table() is not available
                foreach ($formattedResults as $result) {
                    $document = $result['document'] ?? $result;
                    $id = $result['id'] ?? $document['id'] ?? 'N/A';
                    $title = $document['title'] ?? 'N/A';
                    $score = number_format($result['score'] ?? 0, 2);
                    
                    $this->line("- [ID: {$id}] [Score: {$score}] {$title}");
                }
                break;
        }
    }
}

