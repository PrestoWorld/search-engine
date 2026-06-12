<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Tests;

use PHPUnit\Framework\TestCase;
use Prestoworld\SearchEngine\SearchEngine;
use Prestoworld\SearchEngine\SearchResult;
use Cycle\Database\DatabaseInterface;
use PrestoWorld\Modules\Schema\PostRepository;

class SearchEngineTest extends TestCase
{
    protected SearchEngine $searchEngine;
    protected DatabaseInterface $db;
    protected PostRepository $repository;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DatabaseInterface::class);
        $this->repository = $this->createMock(PostRepository::class);
        $this->searchEngine = new SearchEngine($this->db, $this->repository);
    }

    public function test_search_returns_search_result(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('orWhere')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $result = $this->searchEngine->search(['post_type' => 'post']);
        $this->assertInstanceOf(SearchResult::class, $result);
    }

    public function test_search_with_post_type_filter(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->expects($this->once())
            ->method('select')
            ->willReturn($query);

        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['post_type' => 'page']);
    }

    public function test_search_with_author_filter(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['author' => 1]);
    }

    public function test_search_with_keyword(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('orWhere')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['s' => 'test query']);
    }

    public function test_search_with_pagination(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['posts_per_page' => 20, 'paged' => 2]);
    }

    public function test_search_with_any_post_type(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['post_type' => 'any']);
    }

    public function test_search_with_taxonomy_filter(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('innerJoin')->willReturnSelf();
        $query->method('on')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['cat' => 5]);
    }

    public function test_search_with_tag_filter(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('innerJoin')->willReturnSelf();
        $query->method('on')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search(['tag_id' => 10]);
    }

    public function test_search_with_complex_tax_query(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('innerJoin')->willReturnSelf();
        $query->method('on')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([]);

        $this->repository->method('find')->willReturn([]);

        $this->searchEngine->search([
            'tax_query' => [
                ['taxonomy' => 'category', 'terms' => [1, 2, 3]]
            ]
        ]);
    }

    public function test_search_hydrates_results(): void
    {
        $query = $this->createMock(\Cycle\Database\Select\SelectQuery::class);
        $this->db->method('select')->willReturn($query);
        $query->method('from')->willReturnSelf();
        $query->method('limit')->willReturnSelf();
        $query->method('offset')->willReturnSelf();
        $query->method('orderBy')->willReturnSelf();
        $query->method('fetchAll')->willReturn([
            ['id' => 1, 'title' => 'Test Post']
        ]);

        $this->repository->method('find')->willReturn([
            ['id' => 1, 'title' => 'Test Post']
        ]);

        $result = $this->searchEngine->search(['post_type' => 'post']);
        $this->assertNotEmpty($result->getItems());
    }
}
