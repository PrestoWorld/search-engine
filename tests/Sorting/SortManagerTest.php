<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Sorting;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Sorting\SortManager;

class SortManagerTest extends TestCase
{
    private SortManager $manager;

    protected function setUp(): void
    {
        $this->manager = new SortManager(new \TestRequest());
        $this->manager->register('created_at', ['label' => 'Created At', 'direction' => 'desc']);
        $this->manager->register('price', ['label' => 'Price']);
        $this->manager->register('name', ['label' => 'Name', 'direction' => 'asc']);
    }

    public function test_add_appends_sort(): void
    {
        $this->manager->add('created_at', 'desc');
        $sorts = $this->manager->toArray();

        $this->assertCount(1, $sorts);
        $this->assertSame('created_at', $sorts[0]['field']);
        $this->assertSame('desc', $sorts[0]['direction']);
    }

    public function test_register_registers_available_sort(): void
    {
        $this->manager->register('rating', ['label' => 'Rating', 'direction' => 'desc']);
        $available = $this->manager->getAvailableSorts();

        $this->assertArrayHasKey('rating', $available);
        $this->assertSame('Rating', $available['rating']['label']);
    }

    public function test_add_score(): void
    {
        $this->manager->addScore('desc');
        $sorts = $this->manager->toArray();

        $this->assertSame('_score', $sorts[0]['field']);
        $this->assertSame('score', $sorts[0]['type']);
    }

    public function test_add_relevance(): void
    {
        $this->manager->addRelevance();
        $sorts = $this->manager->toArray();

        $this->assertSame('_text_match', $sorts[0]['field']);
        $this->assertSame('relevance', $sorts[0]['type']);
    }

    public function test_add_distance(): void
    {
        $this->manager->addDistance('location', 48.8566, 2.3522);
        $sorts = $this->manager->toArray();

        $this->assertSame('distance', $sorts[0]['type']);
        $this->assertSame(48.8566, $sorts[0]['lat']);
        $this->assertSame(2.3522, $sorts[0]['lng']);
    }

    public function test_add_random(): void
    {
        $this->manager->addRandom();
        $sorts = $this->manager->toArray();

        $this->assertSame('_random', $sorts[0]['field']);
        $this->assertSame('random', $sorts[0]['type']);
    }

    public function test_add_custom(): void
    {
        $func = function () { return 'custom'; };
        $this->manager->addCustom($func);
        $sorts = $this->manager->toArray();

        $this->assertSame('custom', $sorts[0]['type']);
        $this->assertSame($func, $sorts[0]['function']);
    }

    public function test_set_default(): void
    {
        $this->manager->setDefault('price', 'desc');
        $this->manager->applyDefault();
        $sorts = $this->manager->toArray();

        $this->assertSame('price', $sorts[0]['field']);
        $this->assertSame('desc', $sorts[0]['direction']);
    }

    public function test_apply_default_does_nothing_when_sorts_exist(): void
    {
        $this->manager->add('created_at', 'asc');
        $this->manager->applyDefault();
        $sorts = $this->manager->toArray();

        $this->assertCount(1, $sorts);
        $this->assertSame('created_at', $sorts[0]['field']);
    }

    public function test_default_default_is_relevance_desc(): void
    {
        $this->manager->applyDefault();
        $sorts = $this->manager->toArray();

        $this->assertCount(1, $sorts);
        $this->assertSame('relevance', $sorts[0]['field']);
        $this->assertSame('desc', $sorts[0]['direction']);
    }

    public function test_clear(): void
    {
        $this->manager->add('created_at', 'desc');
        $this->manager->add('price', 'asc');
        $this->assertCount(2, $this->manager->toArray());

        $this->manager->clear();
        $this->assertCount(0, $this->manager->toArray());
    }

    public function test_remove(): void
    {
        $this->manager->add('created_at', 'desc');
        $this->manager->add('price', 'asc');

        $this->manager->remove('created_at');
        $sorts = $this->manager->toArray();

        $this->assertCount(1, $sorts);
        $this->assertSame('price', $sorts[0]['field']);
    }

    public function test_to_query_syntax_basic(): void
    {
        $this->manager->add('created_at', 'desc');
        $syntax = $this->manager->toQuerySyntax();

        $this->assertSame('created_at:desc', $syntax);
    }

    public function test_to_query_syntax_multiple(): void
    {
        $this->manager->add('created_at', 'desc');
        $this->manager->add('price', 'asc');
        $syntax = $this->manager->toQuerySyntax();

        $this->assertSame('created_at:desc,price:asc', $syntax);
    }

    public function test_to_query_syntax_score(): void
    {
        $this->manager->addScore('desc');
        $syntax = $this->manager->toQuerySyntax();

        $this->assertSame('_score:desc', $syntax);
    }

    public function test_to_query_syntax_random(): void
    {
        $this->manager->addRandom();
        $syntax = $this->manager->toQuerySyntax();

        $this->assertSame('_random', $syntax);
    }

    public function test_to_query_syntax_distance(): void
    {
        $this->manager->addDistance('location', 48.8566, 2.3522);
        $syntax = $this->manager->toQuerySyntax();

        $this->assertStringContainsString('_geo_distance', $syntax);
    }

    public function test_to_query_syntax_empty_when_no_sorts(): void
    {
        $this->assertSame('', $this->manager->toQuerySyntax());
    }

    public function test_has_sorts(): void
    {
        $this->assertFalse($this->manager->hasSorts());
        $this->manager->add('created_at', 'desc');
        $this->assertTrue($this->manager->hasSorts());
    }

    public function test_get_current_sort(): void
    {
        $this->assertNull($this->manager->getCurrentSort());

        $this->manager->add('created_at', 'desc');
        $current = $this->manager->getCurrentSort();

        $this->assertNotNull($current);
        $this->assertSame('created_at', $current['field']);
    }

    public function test_is_valid_sort_with_registered(): void
    {
        $this->assertTrue($this->manager->isValidSort('created_at'));
        $this->assertTrue($this->manager->isValidSort('price'));
        $this->assertFalse($this->manager->isValidSort('nonexistent'));
    }

    public function test_is_valid_sort_with_special_fields(): void
    {
        $this->assertTrue($this->manager->isValidSort('_score'));
        $this->assertTrue($this->manager->isValidSort('_text_match'));
        $this->assertTrue($this->manager->isValidSort('_random'));
    }

    public function test_get_sort_options(): void
    {
        $options = $this->manager->getSortOptions();

        $this->assertArrayHasKey('created_at', $options);
        $this->assertArrayHasKey('price', $options);
        $this->assertArrayHasKey('name', $options);
        $this->assertSame('Created At', $options['created_at']['label']);
        $this->assertSame('desc', $options['created_at']['default_direction']);
    }

    public function test_get_sort_summary(): void
    {
        $this->assertSame('Relevance', $this->manager->getSortSummary());

        $this->manager->add('created_at', 'desc');
        $summary = $this->manager->getSortSummary();

        $this->assertStringContainsString('Created At', $summary);
    }

    public function test_get_sort_class(): void
    {
        $this->manager->add('created_at', 'asc');
        $this->assertSame('sortable sort-asc', $this->manager->getSortClass('created_at'));
    }

    public function test_get_sort_class_not_current(): void
    {
        $this->manager->add('created_at', 'asc');
        $this->assertSame('sortable', $this->manager->getSortClass('price'));
    }

    public function test_apply_to_calls_builder(): void
    {
        $this->manager->add('created_at', 'desc');
        $this->manager->add('price', 'asc');

        $calls = [];
        $this->manager->applyTo(function ($field, $direction, $options) use (&$calls) {
            $calls[] = [$field, $direction, $options];
        });

        $this->assertCount(2, $calls);
        $this->assertSame(['created_at', 'desc', []], $calls[0]);
        $this->assertSame(['price', 'asc', []], $calls[1]);
    }

    public function test_to_url_params(): void
    {
        $this->manager->add('created_at', 'desc');
        $params = $this->manager->toUrlParams();

        $this->assertArrayHasKey('sort', $params);
        $this->assertStringContainsString('-created_at', $params['sort']);
    }

    public function test_with_relevance_adds_at_front(): void
    {
        $this->manager->add('price', 'asc');
        $this->manager->withRelevance();

        $sorts = $this->manager->toArray();
        $this->assertSame('_text_match', $sorts[0]['field']);
        $this->assertSame('price', $sorts[1]['field']);
    }

    public function test_with_relevance_does_not_duplicate(): void
    {
        $this->manager->addScore();
        $this->manager->withRelevance();

        $this->assertCount(1, $this->manager->toArray());
    }

    public function test_then_by(): void
    {
        $this->manager->add('created_at', 'desc');
        $this->manager->thenBy('price', 'asc');

        $sorts = $this->manager->toArray();
        $this->assertCount(2, $sorts);
        $this->assertSame('price', $sorts[1]['field']);
    }

    public function test_then_by_desc(): void
    {
        $this->manager->add('created_at', 'asc');
        $this->manager->thenByDesc('price');

        $sorts = $this->manager->toArray();
        $this->assertSame('desc', $sorts[1]['direction']);
    }

    public function test_get_sort_class_returns_sort_asc_when_current_asc(): void
    {
        $this->manager->add('price', 'asc');
        $this->assertSame('sortable sort-asc', $this->manager->getSortClass('price'));
    }

    public function test_get_sort_class_returns_sort_desc_when_current_desc(): void
    {
        $this->manager->add('price', 'desc');
        $this->assertSame('sortable sort-desc', $this->manager->getSortClass('price'));
    }

    public function test_to_url_params_returns_empty_when_no_sorts(): void
    {
        $this->assertSame([], $this->manager->toUrlParams());
    }
}
