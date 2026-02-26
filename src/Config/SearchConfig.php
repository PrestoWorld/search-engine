<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Config;

class SearchConfig
{
    private array $config;
    private static ?self $instance = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    public function getAdapterConfig(string $adapter): array
    {
        return $this->config['adapters'][$adapter] ?? [];
    }

    public function setAdapterConfig(string $adapter, array $config): void
    {
        $this->config['adapters'][$adapter] = $config;
    }

    public function getDefaultAdapter(): string
    {
        return $this->config['default_adapter'] ?? 'tntsearch';
    }

    public function setDefaultAdapter(string $adapter): void
    {
        $this->config['default_adapter'] = $adapter;
    }

    public function all(): array
    {
        return $this->config;
    }

    private function getDefaultConfig(): array
    {
        return [
            'default_adapter' => 'tntsearch',
            'adapters' => [
                'tntsearch' => [
                    'storage_path' => storage_path('app/search'),
                    'driver' => 'sqlite',
                    'searchable_fields' => ['title', 'content'],
                    'fuzziness' => false,
                ],
                'typesense' => [
                    'api_key' => env('TYPESENSE_API_KEY', ''),
                    'nodes' => [
                        [
                            'host' => env('TYPESENSE_HOST', 'localhost'),
                            'port' => env('TYPESENSE_PORT', 8108),
                            'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                        ],
                    ],
                    'searchable_fields' => 'title,content',
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'content', 'type' => 'string'],
                        ['name' => 'created_at', 'type' => 'datetime'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'meilisearch' => [
                    'url' => env('MEILISEARCH_URL', 'http://localhost:7700'),
                    'api_key' => env('MEILISEARCH_API_KEY', null),
                    'settings' => [
                        'rankingRules' => [
                            'words',
                            'typo',
                            'proximity',
                            'attribute',
                            'sort',
                            'exactness',
                        ],
                        'searchableAttributes' => ['title', 'content'],
                        'displayedAttributes' => ['*'],
                    ],
                    'highlight_fields' => ['title', 'content'],
                ],
            ],
            'indexing' => [
                'batch_size' => 1000,
                'auto_index' => true,
                'real_time_sync' => false,
            ],
            'search' => [
                'default_limit' => 10,
                'max_limit' => 100,
                'enable_fuzzy' => true,
                'fuzzy_threshold' => 0.8,
            ],
            'performance' => [
                'cache_enabled' => true,
                'cache_ttl' => 3600, // 1 hour
                'enable_benchmark' => false,
            ],
        ];
    }
}
