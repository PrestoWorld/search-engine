<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Config;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Config\SearchConfig;

class SearchConfigTest extends TestCase
{
    private SearchConfig $config;

    protected function setUp(): void
    {
        $this->config = new SearchConfig();
    }

    public function testConstructorMergesConfigWithDefaults(): void
    {
        $config = new SearchConfig(['default_adapter' => 'typesense']);

        $this->assertSame('typesense', $config->getDefaultAdapter());
    }

    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = SearchConfig::getInstance();
        $instance2 = SearchConfig::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testGetReturnsValue(): void
    {
        $this->config->set('test_key', 'test_value');

        $this->assertSame('test_value', $this->config->get('test_key'));
    }

    public function testGetReturnsDefaultWhenNotFound(): void
    {
        $result = $this->config->get('nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $result = $this->config->get('nonexistent');

        $this->assertNull($result);
    }

    public function testSetStoresValue(): void
    {
        $this->config->set('new_key', 'new_value');

        $this->assertSame('new_value', $this->config->get('new_key'));
    }

    public function testGetAdapterConfigReturnsConfig(): void
    {
        $config = $this->config->getAdapterConfig('tntsearch');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('storage_path', $config);
    }

    public function testGetAdapterConfigReturnsEmptyWhenNotFound(): void
    {
        $config = $this->config->getAdapterConfig('nonexistent');

        $this->assertEmpty($config);
    }

    public function testSetAdapterConfigStoresConfig(): void
    {
        $this->config->setAdapterConfig('custom', ['key' => 'value']);

        $config = $this->config->getAdapterConfig('custom');

        $this->assertSame(['key' => 'value'], $config);
    }

    public function testGetDefaultAdapterReturnsDefault(): void
    {
        $adapter = $this->config->getDefaultAdapter();

        $this->assertSame('tntsearch', $adapter);
    }

    public function testSetDefaultAdapterStoresValue(): void
    {
        $this->config->setDefaultAdapter('typesense');

        $this->assertSame('typesense', $this->config->getDefaultAdapter());
    }

    public function testAllReturnsAllConfig(): void
    {
        $all = $this->config->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('default_adapter', $all);
        $this->assertArrayHasKey('adapters', $all);
    }

    public function testDefaultConfigContainsAllRequiredKeys(): void
    {
        $all = $this->config->all();

        $this->assertArrayHasKey('default_adapter', $all);
        $this->assertArrayHasKey('adapters', $all);
        $this->assertArrayHasKey('indexing', $all);
        $this->assertArrayHasKey('search', $all);
        $this->assertArrayHasKey('performance', $all);
    }

    public function testDefaultAdapterConfigs(): void
    {
        $this->assertArrayHasKey('tntsearch', $this->config->getAdapterConfig('tntsearch'));
        $this->assertArrayHasKey('typesense', $this->config->getAdapterConfig('typesense'));
        $this->assertArrayHasKey('meilisearch', $this->config->getAdapterConfig('meilisearch'));
    }
}
