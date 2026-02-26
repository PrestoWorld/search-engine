# PrestoWorld Search Engine - Examples

## 🚀 Advanced Search Examples

### 1. Basic Full-Text Search

```php
use Prestoworld\SearchEngine\SearchManager;

$searchManager = app(SearchManager::class);

// Simple text search
$results = $searchManager->search('articles', 'search query');

// With options
$results = $searchManager->search('articles', 'search query', [
    'limit' => 20,
]);
```

### 2. Advanced Query Builder

```php
use Prestoworld\SearchEngine\QueryBuilder\SearchQueryBuilder;

// Complex search with filters and sorting
$results = SearchQueryBuilder::for('articles')
    ->query('laravel tutorial')
    ->where('status', 'published')
    ->whereBetween('created_at', '2024-01-01', '2024-12-31')
    ->whereIn('category', ['tutorial', 'guide'])
    ->orderBy('created_at', 'desc')
    ->orderBy('title', 'asc')
    ->limit(10)
    ->get();

// Pagination
$paginated = SearchQueryBuilder::for('articles')
    ->query('php')
    ->where('status', 'published')
    ->orderBy('relevance', 'desc')
    ->paginate(15);

echo "Page {$paginated['current_page']} of {$paginated['last_page']}";
```

### 3. Filter System Examples

```php
use Prestoworld\SearchEngine\Filters\FilterManager;

// Create filter manager from request
$filters = FilterManager::fromRequest()
    ->text('title')
    ->select('category')
    ->multiSelect('tags')
    ->range('price', 100, 1000)
    ->dateRange('created_at', '2024-01-01', '2024-12-31')
    ->boolean('featured')
    ->exists('image');

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

### 4. Sorting Examples

```php
use Prestoworld\SearchEngine\Sorting\SortManager;

// Register available sorts
$sortManager = SortManager::fromRequest()
    ->register('price', [
        'field' => 'price',
        'label' => 'Price',
        'description' => 'Sort by price'
    ])
    ->register('date', [
        'field' => 'created_at',
        'label' => 'Date',
        'description' => 'Sort by publication date'
    ])
    ->fromRequest() // Apply sort from URL parameter
    ->withRelevance(); // Always include relevance as primary sort

// Apply to query
$queryBuilder = SearchQueryBuilder::for('articles')
    ->query('laravel');

$sortManager->applyTo(function ($field, $direction) use ($queryBuilder) {
    $queryBuilder->orderBy($field, $direction);
});

$results = $queryBuilder->get();
```

### 5. Form Integration Examples

```php
use Prestoworld\SearchEngine\UI\SearchForm;

// Create search form
$form = SearchForm::create('/search')
    ->queryField('q', app(\Witals\Framework\Http\Request::class)->input('q'))
    ->textFilter('title', [
        'placeholder' => 'Search titles...'
    ])
    ->selectFilter('category', [
        'all' => 'All Categories',
        'tutorial' => 'Tutorials',
        'news' => 'News',
        'guide' => 'Guides'
    ])
    ->multiSelectFilter('tags', [
        'php' => 'PHP',
        'laravel' => 'Laravel',
        'javascript' => 'JavaScript',
        'vue' => 'Vue.js'
    ])
    ->rangeFilter('price', [
        'min_placeholder' => 'Min price',
        'max_placeholder' => 'Max price'
    ])
    ->dateRangeFilter('created_at')
    ->sortSelect([
        'relevance' => 'Relevance',
        'date_desc' => 'Newest First',
        'date_asc' => 'Oldest First',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low'
    ])
    ->autoSubmit(true)
    ->resetButton(true);

// Render form
echo $form->render();

// Apply form to query builder
$queryBuilder = $form->applyToQueryBuilder(
    SearchQueryBuilder::for('products')
);

$results = $queryBuilder->get();
```

### 6. Faceted Search Examples

```php
use Prestoworld\SearchEngine\Aggregations\FacetManager;

// Create facet manager
$facets = FacetManager::for('products')
    ->fromRequest() // Load facet filters from request
    ->facet('category', [
        'label' => 'Categories',
        'size' => 20
    ])
    ->facet('brand', [
        'label' => 'Brands',
        'size' => 15
    ])
    ->rangeFacet('price', [
        ['from' => 0, 'to' => 50, 'label' => 'Under $50'],
        ['from' => 50, 'to' => 100, 'label' => '$50-$100'],
        ['from' => 100, 'to' => 200, 'label' => '$100-$200'],
        ['from' => 200, 'label' => 'Over $200']
    ])
    ->dateHistogramFacet('created_at', 'month', [
        'label' => 'Added Date'
    ]);

// Get facets with search
$facetsData = $facets->getFacets('laptop');

// Render facets
echo $facets->renderFacets('laptop');
echo $facets->renderSelectedFacets();

// Get specific facet values
$categories = $facets->getFacetValues('category', 'laptop');
foreach ($categories as $category) {
    echo "<a href='{$category['url']}'>{$category['label']} ({$category['count']})</a>";
}
```

### 7. Real-World E-commerce Search

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
                'newest' => 'Newest First',
                'rating' => 'Highest Rated'
            ]);

        // Apply form filters
        $form->applyToQueryBuilder($queryBuilder);

        // Add sorting
        $sortManager = SortManager::fromRequest()
            ->register('price', ['field' => 'price'])
            ->register('rating', ['field' => 'rating'])
            ->register('newest', ['field' => 'created_at', 'direction' => 'desc'])
            ->fromRequest()
            ->withRelevance();

        $sortManager->applyTo(function ($field, $direction) use ($queryBuilder) {
            $queryBuilder->orderBy($field, $direction);
        });

        // Get paginated results
        $results = $queryBuilder->paginate(12);

        // Get facets
        $facets = FacetManager::for('products')
            ->fromRequest()
            ->facet('category', ['label' => 'Categories'])
            ->facet('brand', ['label' => 'Brands'])
            ->rangeFacet('price', [
                ['from' => 0, 'to' => 25],
                ['from' => 25, 'to' => 50],
                ['from' => 50, 'to' => 100],
                ['from' => 100, 'to' => 200],
                ['from' => 200]
            ]);

        $facetsData = $facets->getFacets($request->get('q', '*'));

        return view('products.search', [
            'products' => $results,
            'form' => $form->render(),
            'facets' => $facetsData,
            'selectedFacets' => $facets->getSelectedFacets(),
            'query' => $request->get('q'),
        ]);
    }

    private function getCategories(): array
    {
        return Category::pluck('name', 'slug')->toArray();
    }

    private function getBrands(): array
    {
        return Brand::pluck('name', 'slug')->toArray();
    }
}
```

### 8. Blog/Article Search Example

```php
class BlogSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        // Build search with content highlighting
        $results = SearchQueryBuilder::for('articles')
            ->query($query)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->highlightFields(['title', 'content'])
            ->limit(10)
            ->get();

        // Get related articles
        if (!empty($results['results'])) {
            $firstArticle = $results['results'][0]['document'];
            $related = SearchQueryBuilder::for('articles')
                ->where('category', $firstArticle['category'])
                ->where('id', '!=', $firstArticle['id'])
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get();
        }

        // Popular tags from facets
        $facets = FacetManager::for('articles')
            ->facet('tags', ['size' => 15])
            ->getFacets();

        return view('blog.search', [
            'results' => $results,
            'related' => $related ?? [],
            'popularTags' => $facets['tags']['values'] ?? [],
            'query' => $query,
        ]);
    }
}
```

### 9. Multi-Adapter Search Strategy

```php
class SmartSearchService
{
    public function search(string $query, array $options = []): array
    {
        $searchManager = app(SearchManager::class);
        
        // Try different adapters based on query characteristics
        if ($this->isSimpleQuery($query)) {
            // Use TNTSearch for simple queries
            $searchManager->switchAdapter('tntsearch');
        } elseif ($this->needsFuzzySearch($query)) {
            // Use Typesense for typo-tolerant search
            $searchManager->switchAdapter('typesense');
        } else {
            // Use Meilisearch for performance
            $searchManager->switchAdapter('meilisearch');
        }
        
        return $searchManager->search('content', $query, $options);
    }

    private function isSimpleQuery(string $query): bool
    {
        return strlen($query) < 10 && !preg_match('/[^\w\s]/', $query);
    }

    private function needsFuzzySearch(string $query): bool
    {
        return preg_match('/[^\w\s]/', $query) || strlen($query) > 20;
    }
}
```

### 10. Search Analytics and Logging

```php
class SearchAnalyticsService
{
    public function logSearch(string $query, int $resultsCount, float $duration): void
    {
        SearchLog::create([
            'query' => $query,
            'results_count' => $resultsCount,
            'duration_ms' => $duration * 1000,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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

    public function getNoResultQueries(): array
    {
        return SearchLog::selectRaw('query, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->where('results_count', 0)
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }
}
```

## 🎯 Best Practices

### Performance Optimization

```php
// Use specific fields instead of retrieving all
$results = SearchQueryBuilder::for('products')
    ->query('laptop')
    ->select(['id', 'title', 'price', 'image'])
    ->limit(20)
    ->get();

// Cache popular searches
$cacheKey = "search:" . md5($query . serialize($options));
$results = Cache::remember($cacheKey, 3600, function () use ($query, $options) {
    return Search::search('products', $query, $options);
});
```

### Error Handling

```php
try {
    $results = Search::search('products', $query, $options);
} catch (\Exception $e) {
    // Fallback to database search
    $results = $this->fallbackDatabaseSearch($query, $options);
    
    // Log the error
    Log::error('Search engine failed: ' . $e->getMessage());
}
```

### Security

```php
// Always sanitize user input
$query = htmlspecialchars(strip_tags($request->get('q')));
$filters = array_map('htmlspecialchars', $request->only(['category', 'brand']));

// Validate filter values
$allowedCategories = Category::pluck('slug')->toArray();
if (isset($filters['category']) && !in_array($filters['category'], $allowedCategories)) {
    unset($filters['category']);
}
```

These examples demonstrate the full power and flexibility of the PrestoWorld Search Engine, from basic text search to complex e-commerce solutions with faceted navigation and advanced filtering.
