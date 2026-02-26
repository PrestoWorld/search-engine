# PrestoWorld Search Engine - Usage Guide

## 🚀 Quick Start

### Installation

```bash
composer require prestoworld/search-engine
```

### Configuration

Set up your environment variables:

Set up your environment variables:

```env
# Default search engine
SEARCH_ENGINE=meilisearch

# TNTSearch (file-based)
# No additional configuration needed

# Typesense
TYPESENSE_API_KEY=your_api_key
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108
TYPESENSE_PROTOCOL=http

# Meilisearch
MEILISEARCH_URL=http://localhost:7700
MEILISEARCH_API_KEY=your_api_key
```

## 🔧 Basic Usage

### Using the Search Manager

```php
use Prestoworld\SearchEngine\SearchManager;

$searchManager = app(SearchManager::class);

// Index documents
$searchManager->index('articles', [
    [
        'id' => 1,
        'title' => 'Getting Started with Search',
        'content' => 'Learn how to implement search functionality...',
    ],
]);

// Search documents
$results = $searchManager->search('articles', 'search term', [
    'limit' => 10,
]);
```

### Dependency Injection

```php
use Prestoworld\SearchEngine\SearchManager;
use Witals\Framework\Http\Request;

class SearchController
{
    public function __construct(private SearchManager $searchManager)
    {
    }

    public function search(Request $request)
    {
        $results = $this->searchManager->search(
            'articles',
            $request->input('query'),
            ['limit' => $request->input('limit', 10)]
        );

        return $results;
    }
}
```

## 🔄 Adapter Switching

### Runtime Adapter Switching

```php
use Prestoworld\SearchEngine\Facades\Search;

// Switch to Meilisearch
Search::switchAdapter('meilisearch');

// Switch with custom configuration
Search::switchAdapter('typesense', [
    'api_key' => 'custom_key',
    'nodes' => [
        [
            'host' => 'custom.typesense.com',
            'port' => 443,
            'protocol' => 'https',
        ],
    ],
]);

// Get current adapter name
$currentAdapter = Search::getCurrentAdapterName();
```

### Using Multiple Adapters

```php
use Prestoworld\SearchEngine\SearchManager;

$searchManager = new SearchManager();

// Search with TNTSearch
$searchManager->switchAdapter('tntsearch');
$tntResults = $searchManager->search('articles', 'query');

// Search with Meilisearch
$searchManager->switchAdapter('meilisearch');
$meiliResults = $searchManager->search('articles', 'query');
```

## � Advanced Search Features

### Query Builder for Complex Searches

```php
use Prestoworld\SearchEngine\QueryBuilder\SearchQueryBuilder;

// Advanced search with multiple conditions
$results = SearchQueryBuilder::for('products')
    ->query('laptop')
    ->where('status', 'active')
    ->whereBetween('price', 500, 2000)
    ->whereIn('category', ['electronics', 'computers'])
    ->whereLike('brand', 'Apple')
    ->orderBy('price', 'asc')
    ->orderBy('rating', 'desc')
    ->limit(20)
    ->get();

// Pagination
$paginated = SearchQueryBuilder::for('articles')
    ->query('php tutorial')
    ->where('published', true)
    ->orderBy('published_at', 'desc')
    ->paginate(15);
```

### Advanced Filtering System

```php
use Prestoworld\SearchEngine\Filters\FilterManager;

// Create comprehensive filters
$filters = FilterManager::fromRequest()
    ->text('title')                    // Text search with LIKE
    ->exact('category')                 // Exact match
    ->multiSelect('tags')               // Multiple values
    ->range('price', 100, 1000)        // Price range
    ->dateRange('created_at', '2024-01-01', '2024-12-31')
    ->boolean('featured')              // Yes/No filter
    ->exists('image');                  // Has image or not

// Apply to query builder
$queryBuilder = SearchQueryBuilder::for('products');
$filters->applyTo(function ($field, $operator, $value) use ($queryBuilder) {
    match ($operator) {
        '=' => $queryBuilder->where($field, $value),
        'LIKE' => $queryBuilder->whereLike($field, $value),
        'IN' => $queryBuilder->whereIn($field, $value),
        'BETWEEN' => $queryBuilder->whereBetween($field, $value[0], $value[1]),
        default => $queryBuilder->where($field, $operator, $value),
    };
});

$results = $queryBuilder->get();
```

### Advanced Sorting System

```php
use Prestoworld\SearchEngine\Sorting\SortManager;

// Register available sorts
$sortManager = SortManager::fromRequest()
    ->register('price', [
        'field' => 'price',
        'label' => 'Price',
        'description' => 'Sort by price'
    ])
    ->register('rating', [
        'field' => 'rating',
        'label' => 'Customer Rating'
    ])
    ->fromRequest()  // Auto-detect from URL ?sort=price_desc
    ->withRelevance() // Always include relevance first
    ->thenBy('created_at', 'desc');

// Apply to query
$queryBuilder = SearchQueryBuilder::for('products')->query('laptop');
$sortManager->applyTo(function ($field, $direction) use ($queryBuilder) {
    $queryBuilder->orderBy($field, $direction);
});
```

### Faceted Search and Aggregations

```php
use Prestoworld\SearchEngine\Aggregations\FacetManager;

// Create faceted navigation
$facets = FacetManager::for('products')
    ->fromRequest()  // Load facet filters from URL
    ->facet('category', ['label' => 'Categories', 'size' => 20])
    ->facet('brand', ['label' => 'Brands', 'size' => 15])
    ->rangeFacet('price', [
        ['from' => 0, 'to' => 50, 'label' => 'Under $50'],
        ['from' => 50, 'to' => 100, 'label' => '$50-$100'],
        ['from' => 100, 'label' => 'Over $100']
    ])
    ->dateHistogramFacet('created_at', 'month');

// Get facets with search results
$facetsData = $facets->getFacets('laptop');

// Render faceted navigation
echo $facets->renderFacets('laptop');
echo $facets->renderSelectedFacets();
```

## 🎨 Form Integration

### Complete Search Form

```php
use Prestoworld\SearchEngine\UI\SearchForm;

// Create comprehensive search form
$form = SearchForm::create('/search')
    ->queryField('q', $request->get('q'))
    ->textFilter('title', ['placeholder' => 'Search in titles...'])
    ->selectFilter('category', [
        'all' => 'All Categories',
        'electronics' => 'Electronics',
        'books' => 'Books'
    ])
    ->multiSelectFilter('tags', [
        'php' => 'PHP',
        'laravel' => 'Laravel',
        'javascript' => 'JavaScript'
    ])
    ->rangeFilter('price', [
        'min_placeholder' => 'Min',
        'max_placeholder' => 'Max'
    ])
    ->dateRangeFilter('created_at')
    ->checkboxFilter('featured')
    ->sortSelect([
        'relevance' => 'Relevance',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'date_desc' => 'Newest First'
    ])
    ->autoSubmit(true)  // Auto-submit on change
    ->resetButton(true);

// Render form
echo $form->toHtml();

// Apply form to search
$queryBuilder = $form->applyToQueryBuilder(
    SearchQueryBuilder::for('products')
);

$results = $queryBuilder->get();
```

## �📊 Performance Benchmarking

```bash
# Benchmark all adapters
php artisan search:benchmark articles "search query" --iterations=100

# Benchmark specific adapters
php artisan search:benchmark articles "search query" --adapters=meilisearch,typesense
```

### Programmatic Benchmarking

```php
use Prestoworld\SearchEngine\Facades\Search;

$results = Search::benchmark('articles', 'search query', 100);

foreach ($results as $adapter => $data) {
    if (isset($data['error'])) {
        echo "{$adapter}: ERROR - {$data['error']}\n";
    } else {
        echo "{$adapter}: {$data['average_time']}ms avg, {$data['queries_per_second']} queries/sec\n";
    }
}
```

## 🛠 Advanced Configuration

### Custom Adapter Registration

```php
use Prestoworld\SearchEngine\Facades\Search;

// Register a custom adapter
Search::registerAdapter('mysearch', MyCustomAdapter::class);

// Use the custom adapter
Search::switchAdapter('mysearch', $customConfig);
```

### Indexing from Models

```php
// Index all posts
php artisan search:index posts --source="App\Models\Post" --recreate

// Index with specific adapter
php artisan search:index posts --source="App\Models\Post" --adapter=meilisearch
```

### Search Commands

```bash
# Search via CLI
php artisan search:query "search term" articles --limit=5 --adapter=meilisearch

# Output in different formats
php artisan search:query "search term" articles --format=json
php artisan search:query "search term" articles --format=array
```

## 🔍 Search Options

### Common Search Options

```php
$results = Search::search('articles', 'query', [
    'limit' => 20,           // Number of results
    'offset' => 0,           // Pagination offset
    'fuzziness' => true,     // Enable fuzzy search (TNTSearch)
    'sort_by' => 'date:desc', // Sort results (Typesense)
    'attributesToRetrieve' => ['title', 'content'], // Meilisearch
]);
```

### Adapter-Specific Options

#### TNTSearch
```php
$results = Search::search('articles', 'query', [
    'fuzziness' => true,
    'limit' => 10,
]);
```

#### Typesense
```php
$results = Search::search('articles', 'query', [
    'query_by' => 'title,content',
    'sort_by' => 'created_at:desc',
    'filter_by' => 'category:news',
    'page' => 1,
    'per_page' => 10,
]);
```

#### Meilisearch
```php
$results = Search::search('articles', 'query', [
    'attributesToHighlight' => ['title', 'content'],
    'attributesToRetrieve' => ['title', 'content', 'category'],
    'filters' => 'category = "news"',
    'sort' => ['created_at:desc'],
]);
```

## 🎯 Real-World Examples

### E-commerce Product Search

```php
class ProductSearchController extends Controller
{
    public function search(Request $request)
    {
        // Build query with all features
        $queryBuilder = SearchQueryBuilder::for('products')
            ->query($request->get('q'))
            ->where('status', 'active')
            ->where('stock', '>', 0);

        // Apply filters from form
        $form = SearchForm::create('/products/search')
            ->queryField('q', $request->get('q'))
            ->selectFilter('category', $this->getCategories())
            ->multiSelectFilter('brand', $this->getBrands())
            ->rangeFilter('price')
            ->checkboxFilter('free_shipping')
            ->sortSelect([
                'relevance' => 'Relevance',
                'price_asc' => 'Price: Low to High',
                'price_desc' => 'Price: High to Low',
                'newest' => 'Newest First'
            ]);

        $form->applyToQueryBuilder($queryBuilder);

        // Get paginated results
        $results = $queryBuilder->paginate(12);

        // Get facets for navigation
        $facets = FacetManager::for('products')
            ->fromRequest()
            ->facet('category')
            ->facet('brand')
            ->rangeFacet('price', [
                ['from' => 0, 'to' => 50],
                ['from' => 50, 'to' => 100],
                ['from' => 100]
            ]);

        $facetsData = $facets->getFacets($request->get('q', '*'));

        return view('products.search', [
            'products' => $results,
            'form' => $form->render(),
            'facets' => $facetsData,
            'selectedFacets' => $facets->getSelectedFacets(),
        ]);
    }
}
```

### Blog Article Search

```php
class BlogSearchController extends Controller
{
    public function search(Request $request)
    {
        $results = SearchQueryBuilder::for('articles')
            ->query($request->get('q'))
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->highlightFields(['title', 'content'])
            ->paginate(10);

        // Get related articles
        if (!empty($results['data'])) {
            $firstArticle = $results['data'][0]['document'];
            $related = SearchQueryBuilder::for('articles')
                ->where('category', $firstArticle['category'])
                ->where('id', '!=', $firstArticle['id'])
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get();
        }

        return view('blog.search', [
            'results' => $results,
            'related' => $related ?? [],
            'query' => $request->get('q'),
        ]);
    }
}
```

## 🎯 Best Practices

1. **Choose the right adapter:**
   - **TNTSearch**: File-based, good for small to medium datasets
   - **Typesense**: Fast typo-tolerant search, good for user-facing search
   - **Meilisearch**: Lightning-fast, good for real-time search

2. **Batch indexing:**
   ```php
   // Process in batches for large datasets
   $documents = collect($largeDataset)->chunk(1000);
   foreach ($documents as $batch) {
       Search::index('articles', $batch->toArray());
   }
   ```

3. **Error handling:**
   ```php
   try {
       $results = Search::search('articles', $query);
   } catch (\Exception $e) {
       // Fallback to database search or show error
       Log::error('Search failed: ' . $e->getMessage());
       return [];
   }
   ```

4. **Configuration management:**
   - Store sensitive keys in environment variables
   - Use different configurations for development and production
   - Regularly benchmark performance to optimize adapter choice

5. **Performance optimization:**
   - Use specific fields instead of retrieving all data
   - Cache popular search results
   - Implement search analytics for optimization

## 🔧 Troubleshooting

### Common Issues

1. **Adapter not found**: Ensure the adapter is properly configured and dependencies are installed
2. **Connection errors**: Check API keys, URLs, and network connectivity
3. **Index not found**: Make sure to create the index before searching
4. **Performance issues**: Use benchmarking to identify bottlenecks

### Debug Mode

Enable debug mode in configuration:

```php
'performance' => [
    'enable_benchmark' => true,
],
```

This will add timing information to search results.

## 📚 Additional Resources

- [EXAMPLES.md](EXAMPLES.md) - Comprehensive code examples
- [API Documentation](docs/api.md) - Detailed API reference
- [Configuration Guide](docs/configuration.md) - Advanced configuration options
- [Performance Tuning](docs/performance.md) - Optimization techniques
