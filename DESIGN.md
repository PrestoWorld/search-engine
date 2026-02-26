# PrestoWorld Search Engine - Design Documentation

## 📋 Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Design Patterns](#design-patterns)
4. [Component Architecture](#component-architecture)
5. [Adapter Pattern Implementation](#adapter-pattern-implementation)
6. [Data Flow](#data-flow)
7. [Performance Considerations](#performance-considerations)
8. [Extensibility](#extensibility)
9. [Security Considerations](#security-considerations)

## 🎯 Overview

PrestoWorld Search Engine is a high-performance, flexible search infrastructure designed for the PrestoWorld CMS ecosystem. It provides a unified interface for multiple search backends while maintaining adapter-specific optimizations.

### Key Design Goals

- **Flexibility**: Switch between search engines without code changes
- **Performance**: Optimize for each adapter's strengths
- **Extensibility**: Easy to add new search engines
- **Developer Experience**: Intuitive APIs and comprehensive tooling
- **Production Ready**: Robust error handling and monitoring

## 🏗️ Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                       │
├─────────────────────────────────────────────────────────────┤
│  Facade │ Query Builder │ Filter Manager │ Sort Manager   │
├─────────────────────────────────────────────────────────────┤
│                    Search Manager                          │
├─────────────────────────────────────────────────────────────┤
│                Search Engine Interface                     │
├─────────────────────────────────────────────────────────────┤
│  TNTSearch │ Typesense │ Meilisearch │ Custom Adapters   │
├─────────────────────────────────────────────────────────────┤
│                    Storage Layer                           │
└─────────────────────────────────────────────────────────────┘
```

### Core Components

1. **SearchEngineInterface**: Contract for all search adapters
2. **SearchManager**: Central orchestrator and adapter switcher
3. **Adapters**: Implementation for each search engine
4. **QueryBuilder**: Fluent interface for building complex queries
5. **FilterManager**: Advanced filtering capabilities
6. **SortManager**: Flexible sorting options
7. **FacetManager**: Aggregation and faceted search
8. **SearchForm**: UI integration helper

## 🎨 Design Patterns

### 1. Adapter Pattern

**Purpose**: Allow the SearchManager to work with different search engines through a common interface.

**Implementation**:
```php
interface SearchEngineInterface
{
    public function search(string $index, string $query, array $options = []): array;
    public function index(string $index, array $documents): void;
    // ... other methods
}

class TNTSearchAdapter implements SearchEngineInterface { /* ... */ }
class TypesenseAdapter implements SearchEngineInterface { /* ... */ }
class MeilisearchAdapter implements SearchEngineInterface { /* ... */ }
```

**Benefits**:
- Runtime adapter switching
- Easy addition of new search engines
- Consistent API regardless of backend

### 2. Strategy Pattern

**Purpose**: Allow different search strategies based on query characteristics.

**Implementation**:
```php
class SmartSearchService
{
    public function search(string $query): array
    {
        $adapter = $this->selectOptimalAdapter($query);
        $this->searchManager->switchAdapter($adapter);
        return $this->searchManager->search('content', $query);
    }
}
```

### 3. Builder Pattern

**Purpose**: Provide fluent interface for building complex search queries.

**Implementation**:
```php
SearchQueryBuilder::for('products')
    ->query('laptop')
    ->where('status', 'active')
    ->whereBetween('price', 500, 2000)
    ->orderBy('rating', 'desc')
    ->limit(20)
    ->get();
```

### 4. Factory Pattern

**Purpose**: Create appropriate adapter instances based on configuration.

**Implementation**:
```php
class SearchAdapterFactory
{
    public static function create(string $adapterName, array $config): SearchEngineInterface
    {
        return match ($adapterName) {
            'tntsearch' => new TNTSearchAdapter($config),
            'typesense' => new TypesenseAdapter($config),
            'meilisearch' => new MeilisearchAdapter($config),
            default => throw new InvalidArgumentException("Unknown adapter: $adapterName")
        };
    }
}
```

### 5. Singleton Pattern

**Purpose**: Ensure single instance of SearchManager for consistent state.

**Implementation**:
```php
class SearchManager
{
    private static ?self $instance = null;
    
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
}
```

## 🧩 Component Architecture

### SearchEngineInterface

**Role**: Defines contract for all search adapters
**Responsibilities**:
- Standardize search operations
- Ensure consistent return formats
- Provide configuration interface

**Key Methods**:
```php
public function search(string $index, string $query, array $options = []): array;
public function index(string $index, array $documents): void;
public function addDocument(string $index, array $document): void;
public function updateDocument(string $index, string $id, array $document): void;
public function deleteDocument(string $index, string $id): void;
```

### SearchManager

**Role**: Central orchestrator and adapter manager
**Responsibilities**:
- Adapter lifecycle management
- Runtime adapter switching
- Performance benchmarking
- Error handling and fallbacks

**Key Features**:
- Singleton pattern for consistency
- Dynamic adapter registration
- Built-in benchmarking capabilities

### Query Builder

**Role**: Fluent interface for complex query construction
**Responsibilities**:
- Build complex search queries
- Convert to adapter-specific syntax
- Handle pagination and sorting
- Provide type-safe operations

**Design Principles**:
- Method chaining for readability
- Lazy evaluation
- Adapter-agnostic syntax

### Filter Manager

**Role**: Advanced filtering capabilities
**Responsibilities**:
- Parse and validate filter inputs
- Convert to adapter-specific filter syntax
- Handle complex filter combinations
- Support for various filter types

**Filter Types**:
- Text filters (LIKE, exact match)
- Range filters (numeric, date)
- Set filters (IN, NOT IN)
- Boolean filters
- Existence filters

### Sort Manager

**Role**: Flexible sorting system
**Responsibilities**:
- Register available sort options
- Parse sort parameters from requests
- Handle multi-field sorting
- Generate sort URLs for UI

**Sort Types**:
- Field-based sorting
- Relevance-based sorting
- Geographic distance sorting
- Custom sorting functions

### Facet Manager

**Role**: Aggregation and faceted search
**Responsibilities**:
- Define facet configurations
- Execute aggregation queries
- Format facet results
- Handle facet filtering

**Facet Types**:
- Terms facets (categorical data)
- Range facets (numeric/date ranges)
- Histogram facets (statistical distributions)
- Stats facets (aggregated statistics)

## 🔄 Adapter Pattern Implementation

### Interface Design

```php
interface SearchEngineInterface
{
    // Core search operations
    public function search(string $index, string $query, array $options = []): array;
    
    // Document management
    public function index(string $index, array $documents): void;
    public function addDocument(string $index, array $document): void;
    public function updateDocument(string $index, string $id, array $document): void;
    public function deleteDocument(string $index, string $id): void;
    
    // Index management
    public function deleteIndex(string $index): void;
    public function indexExists(string $index): bool;
    
    // Configuration
    public function configure(array $config): void;
    public function getName(): string;
}
```

### Adapter Implementation Strategy

Each adapter handles:
1. **Connection Management**: Establish and maintain connections
2. **Query Translation**: Convert unified options to engine-specific syntax
3. **Result Normalization**: Standardize response format
4. **Error Handling**: Map engine-specific errors to common exceptions

### Example: Typesense Adapter

```php
class TypesenseAdapter implements SearchEngineInterface
{
    private Client $client;
    private array $config;
    
    public function search(string $index, string $query, array $options = []): array
    {
        try {
            $searchParameters = $this->buildSearchParameters($query, $options);
            $results = $this->client->collections[$index]->documents->search($searchParameters);
            return $this->formatResults($results);
        } catch (TypesenseClientError $e) {
            throw new SearchException("Typesense search failed: " . $e->getMessage());
        }
    }
    
    private function buildSearchParameters(string $query, array $options): array
    {
        return array_merge([
            'q' => $query,
            'query_by' => $this->config['searchable_fields'] ?? 'title,content',
            'per_page' => $options['limit'] ?? 10,
        ], $options);
    }
    
    private function formatResults(array $results): array
    {
        return [
            'results' => $this->formatHits($results['hits'] ?? []),
            'found' => $results['found'] ?? 0,
            'page' => $results['page'] ?? 1,
        ];
    }
}
```

## 📊 Data Flow

### Search Request Flow

```
1. Application Request
   ↓
2. Search Facade/Manager
   ↓
3. Query Builder (optional)
   ↓
4. Filter Manager (optional)
   ↓
5. Sort Manager (optional)
   ↓
6. Search Engine Interface
   ↓
7. Specific Adapter
   ↓
8. Search Engine Backend
   ↓
9. Response Normalization
   ↓
10. Application Response
```

### Indexing Flow

```
1. Document Data
   ↓
2. Search Manager
   ↓
3. Adapter Selection
   ↓
4. Document Transformation
   ↓
5. Backend Indexing
   ↓
6. Confirmation
```

### Error Handling Flow

```
1. Error Detection
   ↓
2. Exception Mapping
   ↓
3. Fallback Strategy (if configured)
   ↓
4. Error Response
   ↓
5. Logging & Monitoring
```

## ⚡ Performance Considerations

### Adapter Selection Strategy

```php
class AdapterSelector
{
    public function selectAdapter(string $query, array $context): string
    {
        // Simple queries → TNTSearch (fast, lightweight)
        if (strlen($query) < 10 && !$this->hasSpecialChars($query)) {
            return 'tntsearch';
        }
        
        // Fuzzy matching needed → Typesense
        if ($this->needsFuzzySearch($query)) {
            return 'typesense';
        }
        
        // High performance needed → Meilisearch
        if ($context['high_performance'] ?? false) {
            return 'meilisearch';
        }
        
        return $this->config['default_adapter'];
    }
}
```

### Caching Strategy

```php
class SearchCacheManager
{
    public function getCachedResult(string $key): ?array
    {
        return Cache::remember($key, $this->ttl, function () use ($key) {
            return $this->performSearch($key);
        });
    }
    
    public function invalidateCache(string $index, string $documentId): void
    {
        $pattern = "search:{$index}:*";
        Cache::forget($pattern);
    }
}
```

### Batch Processing

```php
class BatchIndexer
{
    public function indexBatch(string $index, array $documents): void
    {
        $chunks = array_chunk($documents, $this->batchSize);
        
        foreach ($chunks as $chunk) {
            $this->searchManager->index($index, $chunk);
            
            // Prevent memory issues
            if (count($chunk) === $this->batchSize) {
                usleep(100000); // 100ms delay
            }
        }
    }
}
```

## 🔧 Extensibility

### Custom Adapter Registration

```php
// Register custom adapter
SearchManager::getInstance()->registerAdapter('elasticsearch', ElasticsearchAdapter::class);

// Use custom adapter
Search::switchAdapter('elasticsearch', $customConfig);
```

### Custom Filter Types

```php
class CustomFilterManager extends FilterManager
{
    public function geoDistance(string $field, float $lat, float $lng, float $radius): self
    {
        $this->filters[] = [
            'type' => 'geo_distance',
            'field' => $field,
            'lat' => $lat,
            'lng' => $lng,
            'radius' => $radius,
        ];
        
        return $this;
    }
}
```

### Custom Sort Functions

```php
class CustomSortManager extends SortManager
{
    public function addCustomScore(callable $scoreFunction): self
    {
        $this->sorts[] = [
            'field' => '_custom_score',
            'type' => 'custom',
            'function' => $scoreFunction,
        ];
        
        return $this;
    }
}
```

## 🔒 Security Considerations

### Input Validation

```php
class SearchSecurityValidator
{
    public function validateQuery(string $query): string
    {
        // Remove potentially dangerous characters
        $query = preg_replace('/[<>"\']/', '', $query);
        
        // Limit query length
        if (strlen($query) > 1000) {
            throw new InvalidArgumentException('Query too long');
        }
        
        return trim($query);
    }
    
    public function validateFilters(array $filters): array
    {
        $allowedFields = $this->getAllowedFilterFields();
        
        foreach ($filters as $field => $value) {
            if (!in_array($field, $allowedFields)) {
                unset($filters[$field]);
            }
        }
        
        return $filters;
    }
}
```

### Rate Limiting

```php
class SearchRateLimiter
{
    public function checkRateLimit(string $identifier): bool
    {
        $key = "search_rate_limit:{$identifier}";
        $count = Cache::get($key, 0);
        
        if ($count >= $this->maxRequestsPerMinute) {
            return false;
        }
        
        Cache::put($key, $count + 1, 60);
        return true;
    }
}
```

### Access Control

```php
class SearchAccessControl
{
    public function filterResultsByPermission(array $results, User $user): array
    {
        return array_filter($results, function ($result) use ($user) {
            return $this->userCanAccessDocument($user, $result['document']);
        });
    }
}
```

## 📈 Monitoring & Analytics

### Search Analytics

```php
class SearchAnalytics
{
    public function logSearch(string $query, int $resultCount, float $duration): void
    {
        SearchLog::create([
            'query' => $query,
            'results_count' => $resultCount,
            'duration_ms' => $duration * 1000,
            'user_id' => auth()->id(),
            'adapter' => $this->searchManager->getCurrentAdapterName(),
        ]);
    }
    
    public function getPopularQueries(int $limit = 10): array
    {
        return SearchLog::selectRaw('query, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->where('results_count', '>', 0)
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
```

### Performance Monitoring

```php
class SearchPerformanceMonitor
{
    public function monitorSearchPerformance(callable $searchCallback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $results = $searchCallback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $metrics = [
            'duration' => $endTime - $startTime,
            'memory_used' => $endMemory - $startMemory,
            'result_count' => count($results['results'] ?? []),
            'adapter' => $this->searchManager->getCurrentAdapterName(),
        ];
        
        $this->logMetrics($metrics);
        
        return array_merge($results, ['_metrics' => $metrics]);
    }
}
```

## 🎯 Best Practices

### 1. Adapter Selection
- Use TNTSearch for small datasets (< 100K documents)
- Use Typesense for typo-tolerant search needs
- Use Meilisearch for high-performance requirements

### 2. Query Optimization
- Use specific field selection instead of wildcard
- Implement result caching for popular queries
- Use pagination for large result sets

### 3. Error Handling
- Always wrap search calls in try-catch blocks
- Implement fallback strategies
- Log errors for monitoring

### 4. Performance
- Batch index operations when possible
- Use connection pooling for remote adapters
- Monitor memory usage with large datasets

This design documentation provides the foundation for understanding the PrestoWorld Search Engine's architecture and implementation patterns.
