<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\SearchManager;
use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;

class SearchManagerTest extends TestCase
{
    private SearchManager $manager;

    protected function setUp(): void
    {
        $this->manager = new SearchManager();
    }

    public function test_default_adapter_is_tntsearch(): void
    {
        $this->assertSame('TNTSearch', $this->manager->getAdapter()->getName());
    }

    public function test_get_available_adapters(): void
    {
        $adapters = $this->manager->getAvailableAdapters();
        $this->assertArrayHasKey('tntsearch', $adapters);
        $this->assertArrayHasKey('typesense', $adapters);
        $this->assertArrayHasKey('meilisearch', $adapters);
    }

    public function test_set_adapter_switches_implementation(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->method('getName')->willReturn('MockAdapter');

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');

        $this->assertSame('MockAdapter', $this->manager->getCurrentAdapterName());
    }

    public function test_register_adapter_validates_interface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->registerAdapter('invalid', \stdClass::class);
    }

    public function test_register_adapter_validates_class_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->registerAdapter('invalid', 'Nonexistent\\Class');
    }

    public function test_search_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('search')
            ->with('test_index', 'test_query', ['limit' => 10])
            ->willReturn(['results' => [], 'found' => 0]);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');

        $result = $this->manager->search('test_index', 'test_query', ['limit' => 10]);
        $this->assertSame(['results' => [], 'found' => 0], $result);
    }

    public function test_index_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('index')
            ->with('test_index', [['id' => 1]]);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');
        $this->manager->index('test_index', [['id' => 1]]);
    }

    public function test_add_document_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('addDocument')
            ->with('test_index', ['id' => 1]);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');
        $this->manager->addDocument('test_index', ['id' => 1]);
    }

    public function test_update_document_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('updateDocument')
            ->with('test_index', '1', ['title' => 'Updated']);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');
        $this->manager->updateDocument('test_index', '1', ['title' => 'Updated']);
    }

    public function test_delete_document_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('deleteDocument')
            ->with('test_index', '1');

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');
        $this->manager->deleteDocument('test_index', '1');
    }

    public function test_get_document_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('getDocument')
            ->with('test_index', '1')
            ->willReturn(['id' => '1']);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');

        $this->assertSame(['id' => '1'], $this->manager->getDocument('test_index', '1'));
    }

    public function test_delete_index_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('deleteIndex')
            ->with('test_index');

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');
        $this->manager->deleteIndex('test_index');
    }

    public function test_index_exists_delegates_to_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('indexExists')
            ->with('test_index')
            ->willReturn(true);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');

        $this->assertTrue($this->manager->indexExists('test_index'));
    }

    public function test_configure_adapter_delegates(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->expects($this->once())
            ->method('configure')
            ->with(['key' => 'value']);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');
        $this->manager->configureAdapter(['key' => 'value']);
    }

    public function test_switch_adapter_is_alias_for_set_adapter(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->method('getName')->willReturn('MockAdapter');

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->switchAdapter('mock');

        $this->assertSame('MockAdapter', $this->manager->getCurrentAdapterName());
    }

    public function test_get_instance_returns_same_instance(): void
    {
        $instance1 = SearchManager::getInstance(['default_adapter' => 'tntsearch']);
        $instance2 = SearchManager::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_benchmark_runs_all_adapters(): void
    {
        $mock = $this->createMock(SearchEngineInterface::class);
        $mock->method('getName')->willReturn('MockAdapter');
        $mock->method('search')->willReturn(['results' => [], 'found' => 0]);

        $this->manager->registerAdapter('mock', get_class($mock));
        $this->manager->setAdapter('mock');

        $this->manager->getAvailableAdapters();
        $results = $this->manager->benchmark('test_index', 'test', 1);

        $this->assertIsArray($results);
    }

    public function test_constructor_accepts_config(): void
    {
        $config = ['default_adapter' => 'meilisearch'];
        $manager = new SearchManager($config);

        $this->assertSame('meilisearch', $manager->getAdapter()->getName());
    }
}
