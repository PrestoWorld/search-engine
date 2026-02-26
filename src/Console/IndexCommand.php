<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Console;

use Witals\Framework\Console\Command;
use Prestoworld\SearchEngine\SearchManager;

class IndexCommand extends Command
{
    protected string $name = 'search:index';
    protected string $description = 'Index data for search';

    protected array $arguments = [
        'index' => 'The name of the index'
    ];

    protected array $options = [
        '--source' => 'The data source (model, array, etc.)',
        '--adapter' => 'Override the default adapter',
        '--recreate' => 'Delete and recreate the index'
    ];

    public function handle(array $args): int
    {
        $searchManager = $this->app->make(SearchManager::class);
        
        $indexName = $args[0] ?? null;
        if (!$indexName) {
            $this->error("Index name is required");
            return 1;
        }

        $adapter = $this->getOption($args, 'adapter');
        $recreate = $this->hasOption($args, 'recreate');
        $source = $this->getOption($args, 'source');

        if ($adapter) {
            $searchManager->switchAdapter($adapter);
            $this->info("Using adapter: {$adapter}");
        }

        try {
            if ($recreate && $searchManager->indexExists($indexName)) {
                $searchManager->deleteIndex($indexName);
                $this->info("Deleted existing index: {$indexName}");
            }

            $documents = $this->getDocuments($source);
            
            if (empty($documents)) {
                $this->warn("No documents found to index");
                return 0;
            }

            $this->info("Indexing " . count($documents) . " documents...");
            
            $batchSize = 1000;
            $batches = array_chunk($documents, $batchSize);

            foreach ($batches as $index => $batch) {
                $searchManager->index($indexName, $batch);
                $this->line("Indexed batch " . ($index + 1) . "/" . count($batches));
            }

            $this->info("Successfully indexed documents to: {$indexName}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Indexing failed: " . $e->getMessage());
            return 1;
        }
    }

    private function getDocuments(?string $source): array
    {
        if ($source) {
            return $this->getDocumentsFromSource($source);
        }

        // Example documents - replace with actual data source
        return [
            [
                'id' => 1,
                'title' => 'Sample Document 1',
                'content' => 'This is a sample document for testing the search engine.',
                'created_at' => now()->toIso8601String(),
            ],
            [
                'id' => 2,
                'title' => 'Sample Document 2',
                'content' => 'Another sample document with different content.',
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    private function getDocumentsFromSource(string $source): array
    {
        // Implement logic to get documents from different sources
        // e.g., Eloquent models, arrays, APIs, etc.
        
        if (class_exists($source)) {
            $model = new $source;
            if (method_exists($model, 'all')) {
                $results = $model->all();
                return is_array($results) ? $results : (method_exists($results, 'toArray') ? $results->toArray() : (array)$results);
            }
        }

        return [];
    }
}

