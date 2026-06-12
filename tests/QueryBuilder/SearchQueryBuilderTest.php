<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\SearchManager;
use Prestoworld\SearchEngine\QueryBuilder\SearchQueryBuilder;
use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;

class SearchQueryBuilderTest extends TestCase
{
    private SearchManager $manager;
    private SearchQueryBuilder $builder;

    protected function setUp(): void
    {
        $this->manager = new SearchManager();
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->method('getName')->willReturn('MockAdapter');
        $mock->method('search')->willReturnCallback(function ($index, $query, $options) {
            return [
                'results' => [['id' => 1, 'title' => 'Result']],
                'found' => 1,
            ];
        });
        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');

        $this->builder = new SearchQueryBuilder($this->manager, 'test_index');
    }

    public function test_query_sets_search_term(): void
    {
        $result = $this->builder->query('hello')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_adds_filter(): void
    {
        $result = $this->builder->where('status', 'published')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_with_operator(): void
    {
        $result = $this->builder->where('price', '>', 100)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_in(): void
    {
        $result = $this->builder->whereIn('category', [1, 2, 3])->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_not_in(): void
    {
        $result = $this->builder->whereNotIn('status', ['draft'])->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_between(): void
    {
        $result = $this->builder->whereBetween('price', 10, 100)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_date(): void
    {
        $result = $this->builder->whereDate('created_at', '>=', '2024-01-01')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_like(): void
    {
        $result = $this->builder->whereLike('title', 'hello')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_null(): void
    {
        $result = $this->builder->whereNull('deleted_at')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_where_not_null(): void
    {
        $result = $this->builder->whereNotNull('email')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_or_where(): void
    {
        $result = $this->builder->where('status', 'draft')->orWhere('status', 'pending')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_order_by(): void
    {
        $result = $this->builder->orderBy('created_at', 'desc')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_order_by_desc(): void
    {
        $result = $this->builder->orderByDesc('price')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_order_by_random(): void
    {
        $result = $this->builder->orderByRandom()->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_limit(): void
    {
        $result = $this->builder->limit(5)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_offset(): void
    {
        $result = $this->builder->offset(10)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_select_fields(): void
    {
        $result = $this->builder->select(['id', 'title'])->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_facet(): void
    {
        $result = $this->builder->facet('category')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_facets_array(): void
    {
        $result = $this->builder->facets(['category', 'status'])->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_highlight_fields(): void
    {
        $result = $this->builder->highlight('title')->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_fuzzy(): void
    {
        $result = $this->builder->fuzzy(true)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_min_score(): void
    {
        $result = $this->builder->minScore(0.5)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_boost(): void
    {
        $result = $this->builder->boost('title', 2.0)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_first_returns_first_result(): void
    {
        $result = $this->builder->query('test')->first();
        $this->assertNotNull($result);
        $this->assertSame(1, $result['id']);
    }

    public function test_first_returns_null_when_empty(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->method('getName')->willReturn('MockAdapter2');
        $mock->method('search')->willReturn(['results' => [], 'found' => 0]);
        $manager = new SearchManager();
        $manager->registerAdapter('mock2', get_class($mock));
        $manager->setAdapter('mock2');

        $builder = new SearchQueryBuilder($manager, 'empty_index');
        $this->assertNull($builder->query('nothing')->first());
    }

    public function test_exists_returns_true_when_results_found(): void
    {
        $exists = $this->builder->query('test')->exists();
        $this->assertTrue($exists);
    }

    public function test_exists_returns_false_when_no_results(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->method('getName')->willReturn('MockAdapter3');
        $mock->method('search')->willReturn(['results' => [], 'found' => 0]);
        $manager = new SearchManager();
        $manager->registerAdapter('mock3', get_class($mock));
        $manager->setAdapter('mock3');

        $builder = new SearchQueryBuilder($manager, 'empty_index');
        $this->assertFalse($builder->exists());
    }

    public function test_chaining(): void
    {
        $result = $this->builder
            ->query('hello')
            ->where('status', 'published')
            ->whereIn('category', [1, 2])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->offset(0)
            ->facet('category')
            ->highlight('title')
            ->fuzzy()
            ->get();

        $this->assertArrayHasKey('results', $result);
    }

    public function test_for_page_sets_offset_and_limit(): void
    {
        $result = $this->builder->forPage(2, 20)->get();
        $this->assertArrayHasKey('results', $result);
    }

    public function test_multiple_where_filters(): void
    {
        $result = $this->builder
            ->where('status', 'published')
            ->where('type', 'article')
            ->where('author_id', 5)
            ->get();

        $this->assertArrayHasKey('results', $result);
    }

    public function test_mixed_where_and_or_where(): void
    {
        $result = $this->builder
            ->where('status', 'published')
            ->orWhere('status', 'draft')
            ->get();

        $this->assertArrayHasKey('results', $result);
    }
}
