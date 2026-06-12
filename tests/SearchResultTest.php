<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\SearchResult;

class SearchResultTest extends TestCase
{
    public function test_constructor_stores_items_and_total(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $result = new SearchResult($items, 42);

        $this->assertSame($items, $result->getItems());
        $this->assertSame(42, $result->getTotal());
    }

    public function test_empty_items(): void
    {
        $result = new SearchResult([], 0);

        $this->assertSame([], $result->getItems());
        $this->assertSame(0, $result->getTotal());
    }

    public function test_large_total(): void
    {
        $result = new SearchResult([['id' => 1]], 999999);

        $this->assertCount(1, $result->getItems());
        $this->assertSame(999999, $result->getTotal());
    }

    public function test_items_are_returned_by_reference_equivalence(): void
    {
        $items = [['id' => 1, 'title' => 'Test']];
        $result = new SearchResult($items, 1);

        $returned = $result->getItems();
        $this->assertSame('Test', $returned[0]['title']);
    }
}
