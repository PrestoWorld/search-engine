# PrestoWorld Search Engine

[![Latest Version](https://img.shields.io/github/release/prestoworld/search-engine.svg)](https://github.com/prestoworld/search-engine/releases)
[![License](https://img.shields.io/github/license/prestoworld/search-engine.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://php.net)

### 🚀 High-Performance Search Infrastructure for PrestoWorld CMS

The **PrestoWorld Search Engine** is the foundational search layer for the PrestoWorld ecosystem. Engineered for speed, precision, and scalability, this module replaces traditional database lookup methods with a sophisticated indexing and retrieval system designed specifically for the modern web.

---

## ✨ Why PrestoWorld Search?

In a data-driven world, finding content should be instantaneous and intelligent. The PrestoWorld Search Engine moves beyond simple pattern matching to provide a robust architectural framework that understands your data.

* **🔧 Flexible Adapter System**: Switch between TNTSearch, Typesense, Meilisearch, or custom engines without code changes
* **⚡ Lightning Performance**: Optimized indexing algorithms that deliver results in milliseconds, even with massive datasets
* **🎯 Advanced Query Builder**: Fluent interface for complex searches with filters, sorting, and faceted navigation
* **🔍 Full-Text Search**: Deep data indexing with fuzzy matching, highlighting, and relevance scoring
* **📊 Real-time Analytics**: Built-in performance monitoring and search analytics
* **🎨 UI Integration**: Automatic form generation and faceted navigation components
* **🛡️ Production Ready**: Comprehensive error handling, caching, and security features

---

## 🚀 Quick Start

### Installation

```bash
composer require prestoworld/search-engine
```

### Basic Setup

1. **Configure Environment**
Add settings to `.env`:
```env
SEARCH_ENGINE=meilisearch
MEILISEARCH_URL=http://localhost:7700
```

2. **Index Your Data**
```bash
php witals search:index posts --source="App\Models\Post"
```

### Your First Search

```php
use Prestoworld\SearchEngine\SearchManager;
use Prestoworld\SearchEngine\QueryBuilder\SearchQueryBuilder;

// Simple search using manager
$searchManager = app(SearchManager::class);
$results = $searchManager->search('articles', 'search query');

// Advanced search with Query Builder
$results = SearchQueryBuilder::for('products')
    ->query('laptop')
    ->where('status', 'active')
    ->paginate(12);
```

---

## 🎯 Key Features

### 🔧 Multi-Adapter Support

Switch between search engines instantly:

```php
$searchManager = app(SearchManager::class);

// Use TNTSearch for file-based search
$searchManager->switchAdapter('tntsearch');

// Use Typesense for typo-tolerant search
$searchManager->switchAdapter('typesense');

// Use Meilisearch for lightning-fast search
$searchManager->switchAdapter('meilisearch');
```

### 🔍 Advanced Query Builder

Build complex searches with a fluent interface:

```php
$results = SearchQueryBuilder::for('products')
    ->query('smartphone')
    ->where('category', 'electronics')
    ->whereIn('brand', ['Apple', 'Samsung'])
    ->whereBetween('price', 500, 1500)
    ->whereLike('features', 'camera')
    ->orderBy('rating', 'desc')
    ->orderBy('price', 'asc')
    ->limit(20)
    ->get();
```

### 🎛️ Comprehensive Filtering

Advanced filtering system with multiple filter types:

```php
$filters = FilterManager::fromRequest()
    ->text('title')                    // Text search
    ->select('category')                // Dropdown selection
    ->multiSelect('tags')               // Multiple selections
    ->range('price', 100, 1000)        // Price range
    ->dateRange('created_at')           // Date range
    ->boolean('featured')               // Yes/No filter
    ->exists('image');                  // Has image or not
```

### 📊 Faceted Search & Aggregations

Build sophisticated faceted navigation:

```php
$facets = FacetManager::for('products')
    ->facet('category', ['label' => 'Categories'])
    ->facet('brand', ['label' => 'Brands'])
    ->rangeFacet('price', [
        ['from' => 0, 'to' => 50, 'label' => 'Under $50'],
        ['from' => 50, 'to' => 100, 'label' => '$50-$100'],
        ['from' => 100, 'label' => 'Over $100']
    ])
    ->getFacets('laptop');
```

### 🎨 Form Integration

Auto-generate search forms with all features:

```php
$form = SearchForm::create('/search')
    ->queryField('q')
    ->textFilter('title')
    ->selectFilter('category', $categories)
    ->rangeFilter('price')
    ->sortSelect([
        'relevance' => 'Relevance',
        'price_asc' => 'Price: Low to High'
    ]);

echo $form->render();
```

---

## 📖 Documentation

### 📚 Essential Reading

- **[USAGE.md](USAGE.md)** - Complete usage guide with examples
- **[DESIGN.md](DESIGN.md)** - Architecture and design patterns
- **[EXAMPLES.md](EXAMPLES.md)** - Comprehensive code examples

### 🔧 Configuration

#### Search Engines

| Engine | Best For | Performance | Features |
|--------|----------|-------------|----------|
| **TNTSearch** | Small datasets (<100K docs) | ⚡⚡ | File-based, PHP-native |
| **Typesense** | User-facing search | ⚡⚡⚡ | Typo-tolerant, fast |
| **Meilisearch** | High-performance needs | ⚡⚡⚡⚡ | Lightning-fast, real-time |

#### Environment Variables

```env
# General
SEARCH_ENGINE=meilisearch
SEARCH_CACHE_ENABLED=true
SEARCH_BATCH_SIZE=1000

# TNTSearch
SEARCH_STORAGE_PATH=/app/storage/search

# Typesense
TYPESENSE_API_KEY=xyz
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108

# Meilisearch
MEILISEARCH_URL=http://localhost:7700
MEILISEARCH_API_KEY=xyz
```

---

## 🛠️ Real-World Examples

### E-commerce Product Search

```php
class ProductSearchController extends Controller
{
    public function search(Request $request)
    {
        // Build complex product search
        $results = SearchQueryBuilder::for('products')
            ->query($request->get('q'))
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->applyFilters($request)
            ->applySorting($request)
            ->paginate(12);

        // Get faceted navigation
        $facets = FacetManager::for('products')
            ->fromRequest()
            ->facet('category')
            ->facet('brand')
            ->rangeFacet('price')
            ->getFacets($request->get('q', '*'));

        return view('products.search', [
            'products' => $results,
            'facets' => $facets,
            'form' => $this->getSearchForm(),
        ]);
    }
}
```

### Blog Article Search

```php
class BlogController extends Controller
{
    public function search(Request $request)
    {
        $results = SearchQueryBuilder::for('articles')
            ->query($request->get('q'))
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->highlightFields(['title', 'content'])
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return view('blog.search', [
            'articles' => $results,
            'query' => $request->get('q'),
        ]);
    }
}
```

---

## ⚡ Performance Features

### Benchmarking

Compare performance across adapters:

```bash
# Benchmark all adapters
php artisan search:benchmark articles "search query" --iterations=100

# Programmatic benchmarking
$results = Search::benchmark('articles', 'query', 100);
foreach ($results as $adapter => $data) {
    echo "{$adapter}: {$data['average_time']}ms avg\n";
}
```

### Caching

Built-in caching for popular searches:

```php
// Automatic caching
$results = Search::search('products', 'popular query', [
    'cache_ttl' => 3600 // 1 hour
]);

// Manual caching
Cache::remember("search:{$query}", 3600, function () use ($query) {
    return Search::search('products', $query);
});
```

### Batch Operations

Efficient bulk indexing:

```php
// Process in batches
$documents = Product::all()->chunk(1000);
foreach ($documents as $batch) {
    Search::index('products', $batch->toArray());
}
```

---

## 🔧 Advanced Features

### Custom Adapters

Create your own search engine adapter:

```php
class ElasticsearchAdapter implements SearchEngineInterface
{
    public function search(string $index, string $query, array $options = []): array
    {
        // Your Elasticsearch implementation
    }
    
    // Implement other required methods...
}

// Register and use
Search::registerAdapter('elasticsearch', ElasticsearchAdapter::class);
Search::switchAdapter('elasticsearch');
```

### Search Analytics

Built-in analytics and monitoring:

```php
// Log searches automatically
Search::search('products', $query, [
    'log_search' => true,
    'user_id' => auth()->id()
]);

// Get analytics
$analytics = new SearchAnalytics();
$popularQueries = $analytics->getPopularQueries(10);
$noResultQueries = $analytics->getNoResultQueries();
```

### Security Features

Input validation and rate limiting:

```php
// Automatic input sanitization
$results = Search::search('products', $query, [
    'validate_input' => true,
    'max_query_length' => 1000
]);

// Rate limiting
if (!SearchRateLimiter::check($userId)) {
    return response()->json(['error' => 'Rate limit exceeded'], 429);
}
```

---

## 🎯 Use Cases

### 🛒 E-commerce
- Product catalog search with filters
- Price range and category filtering
- Brand and attribute faceting
- Real-time inventory integration

### 📰 Content Management
- Article and blog search
- Full-text content indexing
- Author and category filtering
- Related content recommendations

### 🏢 Enterprise Search
- Document repository search
- Knowledge base integration
- Permission-based filtering
- Advanced query syntax

### 📱 Mobile Applications
- Fast API responses
- Offline search capabilities
- Geospatial search
- Personalized results

---

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

```bash
git clone https://github.com/prestoworld/search-engine.git
cd search-engine
composer install
composer test
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:integration
composer test:performance
```

---

## 📄 License

PrestoWorld Search Engine is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🆘 Support

- **Documentation**: [Full documentation](docs/)
- **Issues**: [GitHub Issues](https://github.com/prestoworld/search-engine/issues)
- **Discussions**: [GitHub Discussions](https://github.com/prestoworld/search-engine/discussions)
- **Email**: support@prestoworld.com

---

## 🗺️ Roadmap

### v1.1 (Planned)
- [ ] GraphQL integration
- [ ] Advanced analytics dashboard
- [ ] Machine learning relevance tuning
- [ ] Multi-tenant support

### v1.2 (Future)
- [ ] Distributed search clusters
- [ ] Real-time collaboration features
- [ ] Advanced A/B testing
- [ ] Voice search integration

---

**PrestoWorld Search Engine** — *Empowering Digital Experiences with Unmatched Search Performance*

---

<div align="center">

**⭐ Star us on GitHub!**

[![GitHub stars](https://img.shields.io/github/stars/prestoworld/search-engine.svg?style=social&label=Star)](https://github.com/prestoworld/search-engine)

</div>
