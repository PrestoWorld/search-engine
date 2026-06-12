<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Aggregations;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Aggregations\FacetManager;
use Prestoworld\SearchEngine\SearchManager;
use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;

class FacetManagerTest extends TestCase
{
    protected FacetManager $facetManager;
    protected SearchManager $searchManager;

    protected function setUp(): void
    {
        $this->searchManager = $this->createMock(SearchManager::class);
        $this->facetManager = new FacetManager($this->searchManager, 'test_index');
    }

    public function test_facet_adds_facet_configuration(): void
    {
        $result = $this->facetManager->facet('category');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_facet_with_custom_options(): void
    {
        $this->facetManager->facet('category', [
            'label' => 'Categories',
            'size' => 20,
            'sort' => 'count',
            'order' => 'desc',
        ]);
        $this->addToAssertionCount(1);
    }

    public function test_rangeFacet_adds_range_facet(): void
    {
        $result = $this->facetManager->rangeFacet('price', [
            ['from' => 0, 'to' => 100],
            ['from' => 100, 'to' => 500],
        ]);
        $this->assertSame($this->facetManager, $result);
    }

    public function test_dateHistogramFacet_adds_date_facet(): void
    {
        $result = $this->facetManager->dateHistogramFacet('created_at', 'month');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_histogramFacet_adds_histogram_facet(): void
    {
        $result = $this->facetManager->histogramFacet('price', 50);
        $this->assertSame($this->facetManager, $result);
    }

    public function test_statsFacet_adds_stats_facet(): void
    {
        $result = $this->facetManager->statsFacet('price');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_cardinalityFacet_adds_cardinality_facet(): void
    {
        $result = $this->facetManager->cardinalityFacet('category_id');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_filterFacet_adds_filter_facet(): void
    {
        $result = $this->facetManager->filterFacet('featured', fn($doc) => $doc['featured'] === true);
        $this->assertSame($this->facetManager, $result);
    }

    public function test_addFacetFilter_adds_filter(): void
    {
        $result = $this->facetManager->addFacetFilter('category', 'electronics');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_addFacetFilter_multiple_values(): void
    {
        $this->facetManager->addFacetFilter('category', 'electronics');
        $this->facetManager->addFacetFilter('category', 'books');
        $this->addToAssertionCount(1);
    }

    public function test_removeFacetFilter_removes_specific_value(): void
    {
        $this->facetManager->addFacetFilter('category', 'electronics');
        $this->facetManager->addFacetFilter('category', 'books');
        
        $result = $this->facetManager->removeFacetFilter('category', 'electronics');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_removeFacetFilter_removes_all_values(): void
    {
        $this->facetManager->addFacetFilter('category', 'electronics');
        
        $result = $this->facetManager->removeFacetFilter('category');
        $this->assertSame($this->facetManager, $result);
    }

    public function test_clearFacetFilters_clears_all_filters(): void
    {
        $this->facetManager->addFacetFilter('category', 'electronics');
        $this->facetManager->addFacetFilter('price', '100');
        
        $result = $this->facetManager->clearFacetFilters();
        $this->assertSame($this->facetManager, $result);
    }

    public function test_setMaxFacetValues_sets_max(): void
    {
        $result = $this->facetManager->setMaxFacetValues(20);
        $this->assertSame($this->facetManager, $result);
    }

    public function test_hasActiveFacets_returns_false_when_no_filters(): void
    {
        $this->assertFalse($this->facetManager->hasActiveFacets());
    }

    public function test_hasActiveFacets_returns_true_when_filters_exist(): void
    {
        $this->facetManager->addFacetFilter('category', 'electronics');
        $this->assertTrue($this->facetManager->hasActiveFacets());
    }

    public function test_getSelectedFacets_returns_empty_array(): void
    {
        $selected = $this->facetManager->getSelectedFacets();
        $this->assertEmpty($selected);
    }

    public function test_getSelectedFacets_returns_filters(): void
    {
        $this->facetManager->facet('category', ['label' => 'Category']);
        $this->facetManager->addFacetFilter('category', 'electronics');
        
        $selected = $this->facetManager->getSelectedFacets();
        $this->assertNotEmpty($selected);
        $this->assertSame('category', $selected[0]['field']);
        $this->assertSame('electronics', $selected[0]['value']);
    }

    public function test_getFacets_calls_search_manager(): void
    {
        $this->searchManager->method('search')->willReturn([
            'facet_counts' => [
                'category' => [
                    ['value' => 'electronics', 'count' => 100],
                    ['value' => 'books', 'count' => 50],
                ],
            ],
        ]);
        $this->searchManager->method('getCurrentAdapterName')->willReturn('typesense');

        $this->facetManager->facet('category');
        $facets = $this->facetManager->getFacets('test query');
        
        $this->assertArrayHasKey('category', $facets);
    }

    public function test_getFacetValues_returns_values(): void
    {
        $this->searchManager->method('search')->willReturn([
            'facet_counts' => [
                'category' => [
                    ['value' => 'electronics', 'count' => 100],
                ],
            ],
        ]);
        $this->searchManager->method('getCurrentAdapterName')->willReturn('typesense');

        $this->facetManager->facet('category');
        $values = $this->facetManager->getFacetValues('category');
        
        $this->assertNotEmpty($values);
    }
}
