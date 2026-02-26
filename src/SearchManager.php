<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine;

use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use Prestoworld\SearchEngine\Adapters\TNTSearchAdapter;
use Prestoworld\SearchEngine\Adapters\TypesenseAdapter;
use Prestoworld\SearchEngine\Adapters\MeilisearchAdapter;

class SearchManager
{
    private ?SearchEngineInterface $adapter = null;
    private array $config;
    private static ?self $instance = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        
        return self::$instance;
    }

    public function setAdapter(string $adapterName, array $config = []): void
    {
        $this->adapter = $this->createAdapter($adapterName, $config);
    }

    public function getAdapter(): SearchEngineInterface
    {
        if ($this->adapter === null) {
            $defaultAdapter = $this->config['default_adapter'] ?? 'tntsearch';
            $this->adapter = $this->createAdapter($defaultAdapter);
        }
        
        return $this->adapter;
    }

    public function switchAdapter(string $adapterName, array $config = []): void
    {
        $this->setAdapter($adapterName, $config);
    }

    public function getAvailableAdapters(): array
    {
        return [
            'tntsearch' => TNTSearchAdapter::class,
            'typesense' => TypesenseAdapter::class,
            'meilisearch' => MeilisearchAdapter::class,
        ];
    }

    public function registerAdapter(string $name, string $className): void
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Adapter class {$className} does not exist");
        }

        if (!is_subclass_of($className, SearchEngineInterface::class)) {
            throw new \InvalidArgumentException("Adapter class {$className} must implement SearchEngineInterface");
        }

        $this->config['custom_adapters'][$name] = $className;
    }

    public function index(string $index, array $documents): void
    {
        $this->getAdapter()->index($index, $documents);
    }

    public function addDocument(string $index, array $document): void
    {
        $this->getAdapter()->addDocument($index, $document);
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        $this->getAdapter()->updateDocument($index, $id, $document);
    }

    public function deleteDocument(string $index, string $id): void
    {
        $this->getAdapter()->deleteDocument($index, $id);
    }

    public function search(string $index, string $query, array $options = []): array
    {
        return $this->getAdapter()->search($index, $query, $options);
    }

    public function getDocument(string $index, string $id): ?array
    {
        return $this->getAdapter()->getDocument($index, $id);
    }

    public function deleteIndex(string $index): void
    {
        $this->getAdapter()->deleteIndex($index);
    }

    public function indexExists(string $index): bool
    {
        return $this->getAdapter()->indexExists($index);
    }

    public function getCurrentAdapterName(): string
    {
        return $this->getAdapter()->getName();
    }

    public function configureAdapter(array $config): void
    {
        $this->getAdapter()->configure($config);
    }

    private function createAdapter(string $adapterName, array $config = []): SearchEngineInterface
    {
        $adapterConfig = array_merge(
            $this->config['adapters'][$adapterName] ?? [],
            $config
        );

        return match ($adapterName) {
            'tntsearch' => new TNTSearchAdapter($adapterConfig),
            'typesense' => new TypesenseAdapter($adapterConfig),
            'meilisearch' => new MeilisearchAdapter($adapterConfig),
            default => $this->createCustomAdapter($adapterName, $adapterConfig),
        };
    }

    private function createCustomAdapter(string $adapterName, array $config): SearchEngineInterface
    {
        $customAdapters = $this->config['custom_adapters'] ?? [];
        
        if (!isset($customAdapters[$adapterName])) {
            throw new \InvalidArgumentException("Unknown adapter: {$adapterName}");
        }

        $className = $customAdapters[$adapterName];
        return new $className($config);
    }

    public function benchmark(string $index, string $query, int $iterations = 100): array
    {
        $results = [];
        $originalAdapter = $this->adapter;

        foreach ($this->getAvailableAdapters() as $name => $class) {
            try {
                $this->setAdapter($name);
                
                $startTime = microtime(true);
                for ($i = 0; $i < $iterations; $i++) {
                    $this->search($index, $query);
                }
                $endTime = microtime(true);
                
                $results[$name] = [
                    'total_time' => $endTime - $startTime,
                    'average_time' => ($endTime - $startTime) / $iterations,
                    'queries_per_second' => $iterations / ($endTime - $startTime),
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->adapter = $originalAdapter;
        return $results;
    }
}
