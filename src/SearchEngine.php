<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Modules\Schema\PostRepository;

/**
 * Advanced Search Engine - The WP_Query Successor
 */
class SearchEngine
{
    protected DatabaseInterface $db;
    protected PostRepository $repository;
    protected string $tablePrefix = 'pw_';

    public function __construct(DatabaseInterface $db, PostRepository $repository)
    {
        $this->db = $db;
        $this->repository = $repository;
    }

    public function search(array $args): SearchResult
    {
        $postTypes = (array) ($args['post_type'] ?? 'post');
        $limit = $args['posts_per_page'] ?? 10;
        $offset = isset($args['paged']) ? ($args['paged'] - 1) * $limit : 0;

        $query = $this->db->select('p.*')
            ->from($this->tablePrefix . 'posts as p');

        // 1. Post Type Filter
        if (!empty($postTypes) && !in_array('any', $postTypes)) {
            $query->where('p.post_type', 'IN', $postTypes);
        }

        // 2. Author Filter
        if (isset($args['author'])) {
            $query->where('p.author_id', (int) $args['author']);
        }

        // 3. Taxonomy Query (The "Search Engine" logic)
        if (isset($args['tax_query']) || isset($args['cat']) || isset($args['tag_id'])) {
            $this->applyTaxonomyFilters($query, $args);
        }

        // 4. Keyword Search
        if (isset($args['s']) && !empty($args['s'])) {
            $term = $args['s'];
            $query->where('p.title', 'LIKE', "%{$term}%")
                  ->orWhere('p.content', 'LIKE', "%{$term}%");
        }

        $query->limit($limit)->offset($offset);
        $query->orderBy('p.created_at', 'DESC');

        $rawResults = $query->fetchAll();
        $hydratedResults = $this->repository->find([
            'post_id' => array_column($rawResults, 'id'),
        ]);

        return new SearchResult($hydratedResults, count($rawResults));
    }

    protected function applyTaxonomyFilters($query, array $args): void
    {
        // Simple cat/tag filtering for speed
        $termIds = [];
        if (isset($args['cat'])) $termIds[] = $args['cat'];
        if (isset($args['tag_id'])) $termIds[] = $args['tag_id'];

        // Handle complex tax_query
        if (isset($args['tax_query'])) {
            foreach ($args['tax_query'] as $tax) {
                if (is_array($tax) && isset($tax['terms'])) {
                    $termIds = array_merge($termIds, (array)$tax['terms']);
                }
            }
        }

        if (!empty($termIds)) {
            $query->innerJoin($this->tablePrefix . 'term_relationships', 'tr')
                  ->on('tr.object_id', 'p.id')
                  ->where('tr.term_id', 'IN', $termIds);
        }
    }
}
