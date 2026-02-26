<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Adapters;

use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use MeiliSearch\Client;
use MeiliSearch\ApiException;

class MeilisearchAdapter implements SearchEngineInterface
{
    private Client $client;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->client = new Client(
            $this->config['url'] ?? 'http://localhost:7700',
            $this->config['api_key'] ?? null
        );
    }

    public function index(string $index, array $documents): void
    {
        try {
            $indexInstance = $this->client->index($index);
            
            // Configure index settings if provided
            if (isset($this->config['settings'])) {
                $indexInstance->updateSettings($this->config['settings']);
            }
            
            $indexInstance->addDocuments($documents);
        } catch (ApiException $e) {
            throw new \RuntimeException("Meilisearch indexing failed: " . $e->getMessage());
        }
    }

    public function addDocument(string $index, array $document): void
    {
        $this->index($index, [$document]);
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        try {
            $indexInstance = $this->client->index($index);
            $document['id'] = $id;
            $indexInstance->addDocuments([$document]);
        } catch (ApiException $e) {
            throw new \RuntimeException("Meilisearch document update failed: " . $e->getMessage());
        }
    }

    public function deleteDocument(string $index, string $id): void
    {
        try {
            $indexInstance = $this->client->index($index);
            $indexInstance->deleteDocument($id);
        } catch (ApiException $e) {
            throw new \RuntimeException("Meilisearch document deletion failed: " . $e->getMessage());
        }
    }

    public function search(string $index, string $query, array $options = []): array
    {
        try {
            $indexInstance = $this->client->index($index);
            
            $searchOptions = array_merge([
                'limit' => $options['limit'] ?? 10,
                'offset' => $options['offset'] ?? 0,
                'attributesToHighlight' => $this->config['highlight_fields'] ?? ['title', 'content'],
                'attributesToRetrieve' => $options['retrieve_fields'] ?? '*',
            ], $options);

            $results = $indexInstance->search($query, $searchOptions);
            
            return $this->formatSearchResults($results);
        } catch (ApiException $e) {
            throw new \RuntimeException("Meilisearch search failed: " . $e->getMessage());
        }
    }

    public function getDocument(string $index, string $id): ?array
    {
        try {
            $indexInstance = $this->client->index($index);
            return $indexInstance->getDocument($id);
        } catch (ApiException $e) {
            return null;
        }
    }

    public function deleteIndex(string $index): void
    {
        try {
            $this->client->deleteIndex($index);
        } catch (ApiException $e) {
            throw new \RuntimeException("Meilisearch index deletion failed: " . $e->getMessage());
        }
    }

    public function indexExists(string $index): bool
    {
        try {
            $this->client->getIndex($index);
            return true;
        } catch (ApiException $e) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'meilisearch';
    }

    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initialize();
    }

    private function formatSearchResults(array $results): array
    {
        $formatted = [];
        
        foreach ($results['hits'] as $hit) {
            $formatted[] = [
                'id' => $hit['id'],
                'score' => $hit['_formatted'] ? 1 : 0, // Meilisearch doesn't provide explicit scores
                'highlights' => $this->extractHighlights($hit),
                'document' => $hit,
            ];
        }
        
        return [
            'results' => $formatted,
            'found' => $results['estimatedTotalHits'] ?? $results['hits'] ?? 0,
            'page' => isset($results['offset']) ? ($results['offset'] / ($results['limit'] ?? 10)) + 1 : 1,
            'per_page' => $results['limit'] ?? 10,
            'processing_time_ms' => $results['processingTimeMs'] ?? 0,
        ];
    }

    private function extractHighlights(array $hit): array
    {
        $highlights = [];
        
        if (isset($hit['_formatted'])) {
            foreach ($hit['_formatted'] as $field => $value) {
                if (is_string($value) && strpos($value, '<em>') !== false) {
                    $highlights[$field] = $value;
                }
            }
        }
        
        return $highlights;
    }
}
