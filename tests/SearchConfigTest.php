<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Config\SearchConfig;

class SearchConfigTest extends TestCase
{
    private SearchConfig $config;

    protected function setUp(): void
    {
        $this->config = new SearchConfig();
    }

    public function test_default_adapter_is_tntsearch(): void
    {
        $this->assertSame('tntsearch', $this->config->getDefaultAdapter());
    }

    public function test_get_returns_default_for_unknown_key(): void
    {
        $this->assertNull($this->config->get('nonexistent'));
        $this->assertSame('fallback', $this->config->get('nonexistent', 'fallback'));
    }

    public function test_set_and_get(): void
    {
        $this->config->set('custom_key', 'custom_value');
        $this->assertSame('custom_value', $this->config->get('custom_key'));
    }

    public function test_set_overwrites_existing(): void
    {
        $this->config->set('default_adapter', 'meilisearch');
        $this->assertSame('meilisearch', $this->config->getDefaultAdapter());
    }

    public function test_set_default_adapter(): void
    {
        $this->config->setDefaultAdapter('typesense');
        $this->assertSame('typesense', $this->config->getDefaultAdapter());
    }

    public function test_get_adapter_config_returns_empty_for_unknown(): void
    {
        $this->assertSame([], $this->config->getAdapterConfig('nonexistent'));
    }

    public function test_get_adapter_config_returns_known_config(): void
    {
        $config = $this->config->getAdapterConfig('tntsearch');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('storage_path', $config);
        $this->assertArrayHasKey('driver', $config);
    }

    public function test_set_adapter_config(): void
    {
        $this->config->setAdapterConfig('custom_adapter', ['key' => 'value']);
        $this->assertSame(['key' => 'value'], $this->config->getAdapterConfig('custom_adapter'));
    }

    public function test_all_returns_full_config(): void
    {
        $all = $this->config->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('default_adapter', $all);
        $this->assertArrayHasKey('adapters', $all);
        $this->assertArrayHasKey('indexing', $all);
        $this->assertArrayHasKey('search', $all);
        $this->assertArrayHasKey('performance', $all);
    }

    public function test_constructor_merges_with_defaults(): void
    {
        $config = new SearchConfig(['default_adapter' => 'meilisearch']);
        $this->assertSame('meilisearch', $config->getDefaultAdapter());
        $all = $config->all();
        $this->assertArrayHasKey('adapters', $all);
        $this->assertArrayHasKey('indexing', $all);
    }

    public function test_singleton(): void
    {
        $config = SearchConfig::getInstance(['default_adapter' => 'typesense']);
        $this->assertSame('typesense', $config->getDefaultAdapter());

        $same = SearchConfig::getInstance();
        $this->assertSame($config, $same);

        SearchConfig::getInstance(['default_adapter' => 'tntsearch']);
        $this->assertSame('typesense', $same->getDefaultAdapter());
    }

    public function test_default_search_config(): void
    {
        $all = $this->config->all();
        $this->assertSame(10, $all['search']['default_limit']);
        $this->assertSame(100, $all['search']['max_limit']);
        $this->assertTrue($all['search']['enable_fuzzy']);
    }

    public function test_default_performance_config(): void
    {
        $all = $this->config->all();
        $this->assertTrue($all['performance']['cache_enabled']);
        $this->assertSame(3600, $all['performance']['cache_ttl']);
        $this->assertFalse($all['performance']['enable_benchmark']);
    }

    public function test_default_indexing_config(): void
    {
        $all = $this->config->all();
        $this->assertSame(1000, $all['indexing']['batch_size']);
        $this->assertTrue($all['indexing']['auto_index']);
        $this->assertFalse($all['indexing']['real_time_sync']);
    }
}
