<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Adapters;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Adapters\MeilisearchAdapter;

class MeilisearchAdapterTest extends TestCase
{
    protected MeilisearchAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new MeilisearchAdapter([
            'url' => 'http://localhost:7700',
            'api_key' => 'test_key',
        ]);
    }

    public function test_getName(): void
    {
        $this->assertSame('meilisearch', $this->adapter->getName());
    }

    public function test_configure_updates_config(): void
    {
        $this->adapter->configure(['highlight_fields' => ['title', 'content']]);
        $this->addToAssertionCount(1); // Should not throw exception
    }

    public function test_indexExists_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->indexExists('test');
    }

    public function test_search_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->search('test', 'query');
    }

    public function test_index_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->index('test', []);
    }

    public function test_addDocument_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->addDocument('test', []);
    }

    public function test_updateDocument_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->updateDocument('test', '1', []);
    }

    public function test_deleteDocument_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->deleteDocument('test', '1');
    }

    public function test_deleteIndex_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->deleteIndex('test');
    }

    public function test_getDocument_returns_null_without_connection(): void
    {
        $result = $this->adapter->getDocument('test', '1');
        $this->assertNull($result);
    }
}
