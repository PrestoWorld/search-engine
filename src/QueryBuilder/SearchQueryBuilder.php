<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\QueryBuilder;

use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use Prestoworld\SearchEngine\SearchManager;

class SearchQueryBuilder
{
    private string $index;
    private SearchManager $searchManager;
    private ?string $query = null;
    private array $filters = [];
    private array $sort = [];
    private array $fields = [];
    private int $limit = 10;
    private int $offset = 0;
    private array $facets = [];
    private array $highlight = [];
    private array $options = [];

    public function __construct(SearchManager $searchManager, string $index)
    {
        $this->searchManager = $searchManager;
        $this->index = $index;
    }

    public static function for(string $index): self
    {
        return new self(app(SearchManager::class), $index);
    }

    public function query(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function where(string $field, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'type' => 'where'
        ];

        return $this;
    }

    public function whereIn(string $field, array $values): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => 'IN',
            'value' => $values,
            'type' => 'where'
        ];

        return $this;
    }

    public function whereNotIn(string $field, array $values): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => 'NOT IN',
            'value' => $values,
            'type' => 'where'
        ];

        return $this;
    }

    public function whereBetween(string $field, mixed $min, mixed $max): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => 'BETWEEN',
            'value' => [$min, $max],
            'type' => 'where'
        ];

        return $this;
    }

    public function whereDate(string $field, string $operator, mixed $value): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'type' => 'date'
        ];

        return $this;
    }

    public function whereLike(string $field, string $value): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => 'LIKE',
            'value' => $value,
            'type' => 'where'
        ];

        return $this;
    }

    public function whereNull(string $field): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => 'NULL',
            'value' => null,
            'type' => 'where'
        ];

        return $this;
    }

    public function whereNotNull(string $field): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => 'NOT NULL',
            'value' => null,
            'type' => 'where'
        ];

        return $this;
    }

    public function orWhere(string $field, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'type' => 'orWhere'
        ];

        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->sort[] = [
            'field' => $field,
            'direction' => strtolower($direction)
        ];

        return $this;
    }

    public function orderByDesc(string $field): self
    {
        return $this->orderBy($field, 'desc');
    }

    public function orderByRandom(): self
    {
        $this->sort[] = [
            'field' => '_random',
            'direction' => 'asc'
        ];

        return $this;
    }

    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function forPage(int $page, int $perPage = 10): self
    {
        $this->offset = ($page - 1) * $perPage;
        $this->limit = $perPage;
        return $this;
    }

    public function facet(string $field): self
    {
        $this->facets[] = $field;
        return $this;
    }

    public function facets(array $fields): self
    {
        $this->facets = array_merge($this->facets, $fields);
        return $this;
    }

    public function highlight(string $field): self
    {
        $this->highlight[] = $field;
        return $this;
    }

    public function highlightFields(array $fields): self
    {
        $this->highlight = array_merge($this->highlight, $fields);
        return $this;
    }

    public function fuzzy(bool $enabled = true): self
    {
        $this->options['fuzziness'] = $enabled;
        return $this;
    }

    public function minScore(float $score): self
    {
        $this->options['min_score'] = $score;
        return $this;
    }

    public function boost(string $field, float $boost): self
    {
        $this->options['boost'][$field] = $boost;
        return $this;
    }

    public function get(): array
    {
        $searchOptions = $this->buildSearchOptions();
        
        if ($this->query) {
            return $this->searchManager->search($this->index, $this->query, $searchOptions);
        }

        // If no text query, use filters only
        return $this->searchWithFiltersOnly($searchOptions);
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results['results'][0] ?? null;
    }

    public function count(): int
    {
        $searchOptions = $this->buildSearchOptions();
        $searchOptions['limit'] = 0;
        
        $results = $this->searchManager->search($this->index, $this->query ?? '*', $searchOptions);
        return $results['found'] ?? 0;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function paginate(int $perPage = 10): array
    {
        $page = (int) (app(\Witals\Framework\Http\Request::class)->input('page', 1));
        $this->forPage($page, $perPage);
        
        $results = $this->get();
        
        return [
            'data' => $results['results'] ?? [],
            'total' => $results['found'] ?? 0,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil(($results['found'] ?? 0) / $perPage),
        ];
    }

    private function buildSearchOptions(): array
    {
        $options = array_merge([
            'limit' => $this->limit,
            'offset' => $this->offset,
        ], $this->options);

        if (!empty($this->fields)) {
            $options['attributesToRetrieve'] = $this->fields;
        }

        if (!empty($this->sort)) {
            $options['sort_by'] = $this->buildSortString();
        }

        if (!empty($this->facets)) {
            $options['facets'] = $this->facets;
        }

        if (!empty($this->highlight)) {
            $options['attributesToHighlight'] = $this->highlight;
        }

        if (!empty($this->filters)) {
            $options['filters'] = $this->buildFilterString();
        }

        return $options;
    }

    private function buildSortString(): string
    {
        $sortParts = [];
        
        foreach ($this->sort as $sort) {
            if ($sort['field'] === '_random') {
                $sortParts[] = '_random';
            } else {
                $sortParts[] = "{$sort['field']}:{$sort['direction']}";
            }
        }
        
        return implode(',', $sortParts);
    }

    private function buildFilterString(): string
    {
        $filterGroups = [];
        $currentGroup = [];
        $lastType = null;

        foreach ($this->filters as $filter) {
            if ($lastType && $filter['type'] !== $lastType && !empty($currentGroup)) {
                $filterGroups[] = [
                    'type' => $lastType,
                    'conditions' => $currentGroup
                ];
                $currentGroup = [];
            }
            
            $currentGroup[] = $filter;
            $lastType = $filter['type'];
        }

        if (!empty($currentGroup)) {
            $filterGroups[] = [
                'type' => $lastType,
                'conditions' => $currentGroup
            ];
        }

        return $this->convertFiltersToSyntax($filterGroups);
    }

    private function convertFiltersToSyntax(array $filterGroups): string
    {
        $adapterName = $this->searchManager->getCurrentAdapterName();
        
        return match ($adapterName) {
            'typesense' => $this->convertToTypesenseFilters($filterGroups),
            'meilisearch' => $this->convertToMeilisearchFilters($filterGroups),
            'tntsearch' => $this->convertToTNTSearchFilters($filterGroups),
            default => $this->convertToTypesenseFilters($filterGroups),
        };
    }

    private function convertToTypesenseFilters(array $filterGroups): string
    {
        $conditions = [];
        
        foreach ($filterGroups as $group) {
            $groupConditions = [];
            
            foreach ($group['conditions'] as $filter) {
                $condition = $this->buildTypesenseCondition($filter);
                if ($condition) {
                    $groupConditions[] = $condition;
                }
            }
            
            if (!empty($groupConditions)) {
                $operator = $group['type'] === 'orWhere' ? ' || ' : ' && ';
                $conditions[] = '(' . implode($operator, $groupConditions) . ')';
            }
        }
        
        return implode(' && ', $conditions);
    }

    private function convertToMeilisearchFilters(array $filterGroups): string
    {
        $conditions = [];
        
        foreach ($filterGroups as $group) {
            $groupConditions = [];
            
            foreach ($group['conditions'] as $filter) {
                $condition = $this->buildMeilisearchCondition($filter);
                if ($condition) {
                    $groupConditions[] = $condition;
                }
            }
            
            if (!empty($groupConditions)) {
                $operator = $group['type'] === 'orWhere' ? ' OR ' : ' AND ';
                $conditions[] = '(' . implode($operator, $groupConditions) . ')';
            }
        }
        
        return implode(' AND ', $conditions);
    }

    private function convertToTNTSearchFilters(array $filterGroups): string
    {
        // TNTSearch has limited filter support
        // Return empty string for now, would need custom implementation
        return '';
    }

    private function buildTypesenseCondition(array $filter): string
    {
        return match ($filter['operator']) {
            '=', '==' => "{$filter['field']}:={$filter['value']}",
            '!=' => "{$filter['field']}:!={$filter['value']}",
            '>' => "{$filter['field']}:>{$filter['value']}",
            '>=' => "{$filter['field']}:>={$filter['value']}",
            '<' => "{$filter['field']}:<{$filter['value']}",
            '<=' => "{$filter['field']}:<={$filter['value']}",
            'IN' => "{$filter['field']}:=[" . implode(',', array_map(fn($v) => "\"$v\"", $filter['value'])) . "]",
            'NOT IN' => "{$filter['field']}:!=[" . implode(',', array_map(fn($v) => "\"$v\"", $filter['value'])) . "]",
            'BETWEEN' => "{$filter['field']}:=[{$filter['value'][0]}..{$filter['value'][1]}]",
            'LIKE' => "{$filter['field']}:={$filter['value']}",
            'NULL' => "{$filter['field']}:=",
            'NOT NULL' => "{$filter['field']}:!=",
            default => null,
        };
    }

    private function buildMeilisearchCondition(array $filter): string
    {
        return match ($filter['operator']) {
            '=', '==' => "{$filter['field']} = \"{$filter['value']}\"",
            '!=' => "{$filter['field']} != \"{$filter['value']}\"",
            '>' => "{$filter['field']} > \"{$filter['value']}\"",
            '>=' => "{$filter['field']} >= \"{$filter['value']}\"",
            '<' => "{$filter['field']} < \"{$filter['value']}\"",
            '<=' => "{$filter['field']} <= \"{$filter['value']}\"",
            'IN' => "{$filter['field']} IN [" . implode(',', array_map(fn($v) => "\"$v\"", $filter['value'])) . "]",
            'NOT IN' => "{$filter['field']} NOT IN [" . implode(',', array_map(fn($v) => "\"$v\"", $filter['value'])) . "]",
            'BETWEEN' => "{$filter['field']} {$filter['value'][0]} TO {$filter['value'][1]}",
            'LIKE' => "{$filter['field']} = \"{$filter['value']}\"",
            'NULL' => "{$filter['field']} IS NULL",
            'NOT NULL' => "{$filter['field']} IS NOT NULL",
            default => null,
        };
    }

    private function searchWithFiltersOnly(array $options): array
    {
        // For adapters that support filter-only search
        $adapterName = $this->searchManager->getCurrentAdapterName();
        
        if ($adapterName === 'typesense') {
            return $this->searchManager->search($this->index, '*', $options);
        }
        
        if ($adapterName === 'meilisearch') {
            return $this->searchManager->search($this->index, '', $options);
        }
        
        // For TNTSearch, return empty results as it doesn't support filter-only search
        return [
            'results' => [],
            'found' => 0,
        ];
    }
}
