<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Sorting;

use Witals\Framework\Http\Request;

class SortManager
{
    private array $sorts = [];
    private array $availableSorts = [];
    private ?Request $request = null;
    private string $defaultSort = 'relevance';
    private string $defaultDirection = 'desc';

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? app(Request::class);
    }

    public function register(string $name, array $config): self
    {
        $this->availableSorts[$name] = array_merge([
            'field' => $name,
            'direction' => 'asc',
            'label' => ucfirst($name),
            'description' => '',
            'type' => 'field',
        ], $config);

        return $this;
    }

    public function add(string $field, string $direction = 'asc', array $options = []): self
    {
        $this->sorts[] = [
            'field' => $field,
            'direction' => strtolower($direction),
            'type' => 'field',
            'options' => $options,
        ];

        return $this;
    }

    public function addScore(string $direction = 'desc'): self
    {
        $this->sorts[] = [
            'field' => '_score',
            'direction' => strtolower($direction),
            'type' => 'score',
        ];

        return $this;
    }

    public function addRelevance(string $direction = 'desc'): self
    {
        $this->sorts[] = [
            'field' => '_text_match',
            'direction' => strtolower($direction),
            'type' => 'relevance',
        ];

        return $this;
    }

    public function addDistance(string $field, float $lat, float $lng, string $direction = 'asc'): self
    {
        $this->sorts[] = [
            'field' => $field,
            'direction' => strtolower($direction),
            'type' => 'distance',
            'lat' => $lat,
            'lng' => $lng,
        ];

        return $this;
    }

    public function addRandom(): self
    {
        $this->sorts[] = [
            'field' => '_random',
            'direction' => 'asc',
            'type' => 'random',
        ];

        return $this;
    }

    public function addCustom(callable $sortFunction, string $direction = 'asc'): self
    {
        $this->sorts[] = [
            'field' => '_custom',
            'direction' => strtolower($direction),
            'type' => 'custom',
            'function' => $sortFunction,
        ];

        return $this;
    }

    public function fromRequest(string $param = 'sort'): self
    {
        $sortParam = $this->request->input($param);
        
        if ($sortParam) {
            $this->parseSortParam($sortParam);
        }

        return $this;
    }

    public function setDefault(string $field, string $direction = 'asc'): self
    {
        $this->defaultSort = $field;
        $this->defaultDirection = strtolower($direction);
        
        return $this;
    }

    public function applyDefault(): self
    {
        if (empty($this->sorts)) {
            $this->add($this->defaultSort, $this->defaultDirection);
        }

        return $this;
    }

    public function clear(): self
    {
        $this->sorts = [];
        return $this;
    }

    public function remove(string $field): self
    {
        $this->sorts = array_filter($this->sorts, function ($sort) use ($field) {
            return $sort['field'] !== $field;
        });

        return $this;
    }

    public function toArray(): array
    {
        return $this->sorts;
    }

    public function toQuerySyntax(): string
    {
        if (empty($this->sorts)) {
            return '';
        }

        $sortParts = [];

        foreach ($this->sorts as $sort) {
            $sortParts[] = $this->buildSortSyntax($sort);
        }

        return implode(',', array_filter($sortParts));
    }

    public function toUrlParams(): array
    {
        $params = [];
        
        if (!empty($this->sorts)) {
            $sortStrings = [];
            
            foreach ($this->sorts as $sort) {
                $sortStrings[] = $this->buildSortString($sort);
            }
            
            $params['sort'] = implode(',', $sortStrings);
        }
        
        return $params;
    }

    public function getAvailableSorts(): array
    {
        return $this->availableSorts;
    }

    public function getSortOptions(): array
    {
        $options = [];

        foreach ($this->availableSorts as $name => $config) {
            $options[$name] = [
                'label' => $config['label'],
                'description' => $config['description'],
                'default_direction' => $config['direction'],
            ];
        }

        return $options;
    }

    public function getCurrentSort(): ?array
    {
        return $this->sorts[0] ?? null;
    }

    public function hasSorts(): bool
    {
        return !empty($this->sorts);
    }

    public function isValidSort(string $field): bool
    {
        return isset($this->availableSorts[$field]) || 
               in_array($field, ['_score', '_text_match', '_random', '_custom']);
    }

    public function getSortUrl(string $field, string $direction = 'asc'): string
    {
        $currentParams = $this->request->query();
        $currentParams['sort'] = $direction === 'desc' ? "-{$field}" : $field;

        return $this->request->path() . '?' . http_build_query($currentParams);
    }

    public function getToggleSortUrl(string $field): string
    {
        $currentSort = $this->getCurrentSort();
        
        if ($currentSort && $currentSort['field'] === $field) {
            $newDirection = $currentSort['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $newDirection = $this->availableSorts[$field]['direction'] ?? 'asc';
        }

        return $this->getSortUrl($field, $newDirection);
    }

    public function getSortClass(string $field): string
    {
        $currentSort = $this->getCurrentSort();
        
        if (!$currentSort || $currentSort['field'] !== $field) {
            return 'sortable';
        }

        return 'sortable ' . ($currentSort['direction'] === 'asc' ? 'sort-asc' : 'sort-desc');
    }

    private function parseSortParam(string $sortParam): void
    {
        $sortFields = explode(',', $sortParam);

        foreach ($sortFields as $sortField) {
            $direction = 'asc';
            
            if (str_starts_with($sortField, '-')) {
                $field = substr($sortField, 1);
                $direction = 'desc';
            } else {
                $field = $sortField;
            }

            if ($this->isValidSort($field)) {
                $this->add($field, $direction);
            }
        }
    }

    private function buildSortSyntax(array $sort): string
    {
        return match ($sort['type']) {
            'field' => "{$sort['field']}:{$sort['direction']}",
            'score' => "_score:{$sort['direction']}",
            'relevance' => "_text_match:{$sort['direction']}",
            'distance' => $this->buildDistanceSyntax($sort),
            'random' => "_random",
            'custom' => $this->buildCustomSyntax($sort),
            default => '',
        };
    }

    private function buildSortString(array $sort): string
    {
        return $sort['direction'] === 'desc' ? "-{$sort['field']}" : $sort['field'];
    }

    private function buildDistanceSyntax(array $sort): string
    {
        // Implementation depends on the search engine
        // For Typesense: sort_by=_geo_distance(location, lat, lng):asc
        // For Meilisearch: sort=_geoPoint(lat, lng):asc
        
        return "_geo_distance({$sort['field']}, {$sort['lat']}, {$sort['lng']}):{$sort['direction']}";
    }

    private function buildCustomSyntax(array $sort): string
    {
        // Custom sort functions would need to be implemented per adapter
        return '';
    }

    public function applyTo(callable $builder): void
    {
        foreach ($this->sorts as $sort) {
            $builder($sort['field'], $sort['direction'], $sort['options'] ?? []);
        }
    }

    
    public function getSortSummary(): string
    {
        if (empty($this->sorts)) {
            return 'Relevance';
        }

        $summaries = [];
        
        foreach ($this->sorts as $sort) {
            $label = $this->getSortLabel($sort);
            if ($label) {
                $summaries[] = $label;
            }
        }

        return implode(', ', $summaries);
    }

    private function getSortLabel(array $sort): string
    {
        $field = $sort['field'];
        $direction = $sort['direction'] === 'asc' ? '↑' : '↓';

        if (isset($this->availableSorts[$field])) {
            return $this->availableSorts[$field]['label'] . " {$direction}";
        }

        return match ($field) {
            '_score' => "Score {$direction}",
            '_text_match' => "Relevance {$direction}",
            '_random' => "Random",
            '_geo_distance' => "Distance {$direction}",
            default => ucfirst($field) . " {$direction}",
        };
    }

    public function withRelevance(): self
    {
        // Add relevance as primary sort if not already present
        $hasRelevance = false;
        foreach ($this->sorts as $sort) {
            if (in_array($sort['type'], ['score', 'relevance'])) {
                $hasRelevance = true;
                break;
            }
        }

        if (!$hasRelevance) {
            array_unshift($this->sorts, [
                'field' => '_text_match',
                'direction' => 'desc',
                'type' => 'relevance',
            ]);
        }

        return $this;
    }

    public function thenBy(string $field, string $direction = 'asc'): self
    {
        return $this->add($field, $direction);
    }

    public function thenByDesc(string $field): self
    {
        return $this->add($field, 'desc');
    }
}
