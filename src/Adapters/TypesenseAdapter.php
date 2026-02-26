<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Adapters;

use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use Typesense\Client;
use Typesense\Exceptions\TypesenseClientError;

class TypesenseAdapter implements SearchEngineInterface
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
        $this->client = new Client([
            'api_key' => $this->config['api_key'] ?? '',
            'nodes' => $this->config['nodes'] ?? [
                [
                    'host' => $this->config['host'] ?? 'localhost',
                    'port' => $this->config['port'] ?? 8108,
                    'protocol' => $this->config['protocol'] ?? 'http',
                ],
            ],
            'nearest_node' => $this->config['nearest_node'] ?? null,
            'connection_timeout_seconds' => $this->config['connection_timeout_seconds'] ?? 2,
            'healthcheck_interval_seconds' => $this->config['healthcheck_interval_seconds'] ?? 30,
            'num_retries' => $this->config['num_retries'] ?? 3,
            'retry_interval_seconds' => $this->config['retry_interval_seconds'] ?? 1,
        ]);
    }

    public function index(string $index, array $documents): void
    {
        try {
            if (!$this->indexExists($index)) {
                $this->createCollection($index);
            }

            $this->client->collections[$index]->documents->import($documents);
        } catch (TypesenseClientError $e) {
            throw new \RuntimeException("Typesense indexing failed: " . $e->getMessage());
        }
    }

    public function addDocument(string $index, array $document): void
    {
        try {
            if (!$this->indexExists($index)) {
                $this->createCollection($index);
            }

            $this->client->collections[$index]->documents->create($document);
        } catch (TypesenseClientError $e) {
            throw new \RuntimeException("Typesense document creation failed: " . $e->getMessage());
        }
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        try {
            $this->client->collections[$index]->documents[$id]->update($document);
        } catch (TypesenseClientError $e) {
            throw new \RuntimeException("Typesense document update failed: " . $e->getMessage());
        }
    }

    public function deleteDocument(string $index, string $id): void
    {
        try {
            $this->client->collections[$index]->documents[$id]->delete();
        } catch (TypesenseClientError $e) {
            throw new \RuntimeException("Typesense document deletion failed: " . $e->getMessage());
        }
    }

    public function search(string $index, string $query, array $options = []): array
    {
        try {
            $searchParameters = array_merge([
                'q' => $query,
                'query_by' => $this->config['searchable_fields'] ?? 'title,content',
                'page' => $options['page'] ?? 1,
                'per_page' => $options['limit'] ?? 10,
                'sort_by' => $options['sort_by'] ?? '_text_match:desc',
            ], $options);

            $results = $this->client->collections[$index]->documents->search($searchParameters);
            
            return $this->formatSearchResults($results);
        } catch (TypesenseClientError $e) {
            throw new \RuntimeException("Typesense search failed: " . $e->getMessage());
        }
    }

    public function getDocument(string $index, string $id): ?array
    {
        try {
            return $this->client->collections[$index]->documents[$id]->retrieve();
        } catch (TypesenseClientError $e) {
            return null;
        }
    }

    public function deleteIndex(string $index): void
    {
        try {
            $this->client->collections[$index]->delete();
        } catch (TypesenseClientError $e) {
            throw new \RuntimeException("Typesense collection deletion failed: " . $e->getMessage());
        }
    }

    public function indexExists(string $index): bool
    {
        try {
            $this->client->collections[$index]->retrieve();
            return true;
        } catch (TypesenseClientError $e) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'typesense';
    }

    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initialize();
    }

    private function createCollection(string $index): void
    {
        $schema = [
            'name' => $index,
            'fields' => $this->config['fields'] ?? [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'content', 'type' => 'string'],
                ['name' => 'created_at', 'type' => 'datetime'],
            ],
            'default_sorting_field' => $this->config['default_sorting_field'] ?? 'created_at',
        ];

        $this->client->collections->create($schema);
    }

    private function formatSearchResults(array $results): array
    {
        $formatted = [];
        
        foreach ($results['hits'] as $hit) {
            $formatted[] = [
                'id' => $hit['document']['id'],
                'score' => $hit['text_match'] ?? 0,
                'highlights' => $hit['highlights'] ?? [],
                'document' => $hit['document'],
            ];
        }
        
        return [
            'results' => $formatted,
            'found' => $results['found'] ?? 0,
            'page' => $results['page'] ?? 1,
            'per_page' => $results['request_params']['per_page'] ?? 10,
        ];
    }
}
