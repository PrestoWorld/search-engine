<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Adapters;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Adapters\TypesenseAdapter;

class TypesenseAdapterTest extends TestCase
{
    protected TypesenseAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new TypesenseAdapter([
            'api_key' => 'test_key',
            'nodes' => [
                [
                    'host' => 'localhost',
                    'port' => 8108,
                    'protocol' => 'http',
                ],
            ],
        ]);
    }

    public function test_getName(): void
    {
        $this->assertSame('typesense', $this->adapter->getName());
    }

    public function test_configure_updates_config(): void
    {
        $this->adapter->configure(['searchable_fields' => 'title,content']);
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

    public function test_getDocument_throws_exception_without_connection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter->getDocument('test', '1');
    }
}
