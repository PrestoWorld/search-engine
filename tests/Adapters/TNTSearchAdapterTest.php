<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests\Adapters;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\Adapters\TNTSearchAdapter;

class TNTSearchAdapterTest extends TestCase
{
    protected TNTSearchAdapter $adapter;
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/tntsearch_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->adapter = new TNTSearchAdapter([
            'storage_path' => $this->tmpDir,
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    protected function rmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_getName(): void
    {
        $this->assertSame('tntsearch', $this->adapter->getName());
    }

    public function test_indexExists_returns_false_for_nonexistent_index(): void
    {
        $this->assertFalse($this->adapter->indexExists('nonexistent'));
    }

    public function test_indexExists_returns_true_for_existing_index(): void
    {
        $indexPath = $this->tmpDir . '/test.index';
        file_put_contents($indexPath, 'test');

        $this->assertTrue($this->adapter->indexExists('test'));
    }

    public function test_deleteIndex_removes_index_file(): void
    {
        $indexPath = $this->tmpDir . '/test.index';
        file_put_contents($indexPath, 'test');

        $this->adapter->deleteIndex('test');
        $this->assertFileDoesNotExist($indexPath);
    }

    public function test_deleteIndex_handles_nonexistent_index(): void
    {
        $this->adapter->deleteIndex('nonexistent');
        $this->addToAssertionCount(1); // Should not throw exception
    }

    public function test_getDocument_returns_null(): void
    {
        $result = $this->adapter->getDocument('test', '1');
        $this->assertNull($result);
    }

    public function test_configure_updates_config(): void
    {
        $this->adapter->configure(['searchable_fields' => ['title', 'description']]);
        $this->addToAssertionCount(1); // Should not throw exception
    }

    public function test_search_returns_empty_array_for_nonexistent_index(): void
    {
        $result = $this->adapter->search('nonexistent', 'test query');
        $this->assertSame([], $result);
    }

    public function test_search_with_options(): void
    {
        $result = $this->adapter->search('nonexistent', 'test query', [
            'limit' => 20,
            'fuzziness' => true,
        ]);
        $this->assertSame([], $result);
    }
}
