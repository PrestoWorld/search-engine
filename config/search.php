<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search engine that will be used by the
    | search manager. You can switch between 'tntsearch', 'typesense', or 
    | 'meilisearch' at runtime.
    |
    */
    'default_adapter' => env('SEARCH_ENGINE', 'tntsearch'),

    /*
    |--------------------------------------------------------------------------
    | Search Engine Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the search engines that your application
    | supports. Each engine has its own configuration options.
    |
    */
    'adapters' => [
        'tntsearch' => [
            'storage_path' => storage_path('app/search'),
            'driver' => 'sqlite',
            'searchable_fields' => ['title', 'content'],
            'fuzziness' => false,
        ],

        'typesense' => [
            'api_key' => env('TYPESENSE_API_KEY'),
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
            'api_key' => env('MEILISEARCH_API_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | Indexing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how documents are indexed across different search engines.
    |
    */
    'indexing' => [
        'batch_size' => env('SEARCH_BATCH_SIZE', 1000),
        'auto_index' => env('SEARCH_AUTO_INDEX', true),
        'real_time_sync' => env('SEARCH_REAL_TIME_SYNC', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Default search parameters that will be applied to all search queries.
    |
    */
    'search' => [
        'default_limit' => env('SEARCH_DEFAULT_LIMIT', 10),
        'max_limit' => env('SEARCH_MAX_LIMIT', 100),
        'enable_fuzzy' => env('SEARCH_ENABLE_FUZZY', true),
        'fuzzy_threshold' => env('SEARCH_FUZZY_THRESHOLD', 0.8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for the search engine.
    |
    */
    'performance' => [
        'cache_enabled' => env('SEARCH_CACHE_ENABLED', true),
        'cache_ttl' => env('SEARCH_CACHE_TTL', 3600),
        'enable_benchmark' => env('SEARCH_ENABLE_BENCHMARK', false),
    ],
];
