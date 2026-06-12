<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Filters;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Filters\FilterManager;
use Witals\Framework\Http\Request;

class FilterManagerTest extends TestCase
{
    private FilterManager $manager;

    protected function setUp(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('input')->willReturn(null);
        $this->manager = new FilterManager($request);
    }

    public function test_text_adds_filter_with_like_operator(): void
    {
        $this->manager->text('title', 'hello');
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('text', $filters[0]['type']);
        $this->assertSame('title', $filters[0]['field']);
        $this->assertSame('hello', $filters[0]['value']);
        $this->assertSame('LIKE', $filters[0]['operator']);
    }

    public function test_exact_adds_filter_with_equals_operator(): void
    {
        $this->manager->exact('status', 'published');
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('exact', $filters[0]['type']);
        $this->assertSame('=', $filters[0]['operator']);
    }

    public function test_select_adds_filter(): void
    {
        $this->manager->select('category', 'tech');
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('select', $filters[0]['type']);
    }

    public function test_multi_select_splits_comma_string(): void
    {
        $this->manager->multiSelect('tags', ['php', 'laravel']);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('multiselect', $filters[0]['type']);
        $this->assertSame(['php', 'laravel'], $filters[0]['value']);
    }

    public function test_multi_select_filters_empty_values(): void
    {
        $this->manager->multiSelect('tags', ['php', '', null]);
        $filters = $this->manager->toArray();

        $this->assertSame(['php'], array_values($filters[0]['value']));
    }

    public function test_range_with_both_ends(): void
    {
        $this->manager->range('price', 10, 100);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('range', $filters[0]['type']);
        $this->assertSame(10, $filters[0]['min']);
        $this->assertSame(100, $filters[0]['max']);
    }

    public function test_range_with_min_only(): void
    {
        $this->manager->range('price', 50, null);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame(50, $filters[0]['min']);
        $this->assertNull($filters[0]['max']);
    }

    public function test_range_skips_when_both_null(): void
    {
        $this->manager->range('price', null, null);
        $this->assertCount(0, $this->manager->toArray());
    }

    public function test_date_range(): void
    {
        $this->manager->dateRange('created_at', '2024-01-01', '2024-12-31');
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('date_range', $filters[0]['type']);
        $this->assertSame('BETWEEN', $filters[0]['operator']);
    }

    public function test_date(): void
    {
        $this->manager->date('published_at', '2024-06-01');
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('date', $filters[0]['type']);
        $this->assertSame('=', $filters[0]['operator']);
    }

    public function test_boolean(): void
    {
        $this->manager->boolean('is_featured', true);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('boolean', $filters[0]['type']);
        $this->assertTrue($filters[0]['value']);
    }

    public function test_exists_with_true(): void
    {
        $this->manager->exists('deleted_at', true);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('NOT NULL', $filters[0]['operator']);
    }

    public function test_exists_with_false(): void
    {
        $this->manager->exists('deleted_at', false);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('NULL', $filters[0]['operator']);
    }

    public function test_custom_filter(): void
    {
        $this->manager->register('custom_range', function (string $field, int $min, int $max) {
            return [
                'type' => 'custom',
                'field' => $field,
                'operator' => 'BETWEEN',
                'value' => [$min, $max],
            ];
        });

        $this->manager->custom('custom_range', 'price', 10, 100);
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('custom', $filters[0]['type']);
    }

    public function test_custom_ignores_unregistered(): void
    {
        $this->manager->custom('nonexistent', 'field');
        $this->assertCount(0, $this->manager->toArray());
    }

    public function test_has_active_filters(): void
    {
        $this->assertFalse($this->manager->hasActiveFilters());
        $this->manager->text('title', 'hello');
        $this->assertTrue($this->manager->hasActiveFilters());
    }

    public function test_get_active_filters(): void
    {
        $this->manager->text('title', 'hello');
        $this->manager->range('price', null, null);

        $active = $this->manager->getActiveFilters();
        $this->assertCount(1, $active);
        $this->assertSame('title', $active[0]['field']);
    }

    public function test_clear_removes_all_filters(): void
    {
        $this->manager->text('title', 'hello');
        $this->manager->exact('status', 'published');
        $this->assertCount(2, $this->manager->toArray());

        $this->manager->clear();
        $this->assertCount(0, $this->manager->toArray());
    }

    public function test_remove_by_field(): void
    {
        $this->manager->text('title', 'hello');
        $this->manager->exact('status', 'published');

        $this->manager->remove('title');
        $filters = $this->manager->toArray();

        $this->assertCount(1, $filters);
        $this->assertSame('status', $filters[0]['field']);
    }

    public function test_apply_to_calls_builder(): void
    {
        $this->manager->text('title', 'hello');
        $this->manager->exact('status', 'published');

        $calls = [];
        $this->manager->applyTo(function ($field, $operator, $value) use (&$calls) {
            $calls[] = [$field, $operator, $value];
        });

        $this->assertCount(2, $calls);
        $this->assertSame(['title', 'LIKE', '%hello%'], $calls[0]);
        $this->assertSame(['status', '=', 'published'], $calls[1]);
    }

    public function test_to_query_params(): void
    {
        $this->manager->text('search', 'hello');
        $this->manager->range('price', 10, 100);
        $this->manager->multiSelect('tags', ['php', 'js']);

        $params = $this->manager->toQueryParams();

        $this->assertArrayHasKey('search', $params);
        $this->assertArrayHasKey('price_min', $params);
        $this->assertArrayHasKey('price_max', $params);
        $this->assertArrayHasKey('tags', $params);
        $this->assertSame('php,js', $params['tags']);
    }

    public function test_register_adds_custom_filter(): void
    {
        $mockFilter = function () {
            return ['type' => 'custom_mock', 'field' => 'test'];
        };

        $this->manager->register('mock_filter', $mockFilter);
        $this->manager->custom('mock_filter');

        $filters = $this->manager->toArray();
        $this->assertCount(1, $filters);
        $this->assertSame('custom_mock', $filters[0]['type']);
    }

    public function test_get_filter_summary(): void
    {
        $this->manager->text('search', 'hello');
        $this->manager->range('price', 10, 100);

        $summary = $this->manager->getFilterSummary();
        $this->assertNotEmpty($summary);
        $this->assertStringContainsString('search', $summary[0]);
    }

    public function test_from_request(): void
    {
        $manager = FilterManager::fromRequest();
        $this->assertInstanceOf(FilterManager::class, $manager);
    }
}
