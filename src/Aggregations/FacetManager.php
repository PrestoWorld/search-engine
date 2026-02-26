<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Aggregations;

use Prestoworld\SearchEngine\SearchManager;
use Witals\Framework\Http\Request;

class FacetManager
{
    private SearchManager $searchManager;
    private string $index;
    private array $facets = [];
    private array $facetFilters = [];
    private int $maxFacetValues = 10;

    public function __construct(SearchManager $searchManager, string $index)
    {
        $this->searchManager = $searchManager;
        $this->index = $index;
    }

    public static function for(string $index): self
    {
        return new self(app(SearchManager::class), $index);
    }

    public function facet(string $field, array $options = []): self
    {
        $this->facets[$field] = array_merge([
            'field' => $field,
            'label' => ucfirst($field),
            'type' => 'terms',
            'size' => $this->maxFacetValues,
            'sort' => 'count', // count, key, _key
            'order' => 'desc', // desc, asc
            'missing' => true,
            'filters' => [],
        ], $options);

        return $this;
    }

    public function rangeFacet(string $field, array $ranges, array $options = []): self
    {
        $this->facets[$field] = array_merge([
            'field' => $field,
            'label' => ucfirst($field),
            'type' => 'range',
            'ranges' => $ranges,
            'missing' => true,
        ], $options);

        return $this;
    }

    public function dateHistogramFacet(string $field, string $interval = 'month', array $options = []): self
    {
        $this->facets[$field] = array_merge([
            'field' => $field,
            'label' => ucfirst($field),
            'type' => 'date_histogram',
            'interval' => $interval,
            'format' => 'yyyy-MM-dd',
            'missing' => true,
        ], $options);

        return $this;
    }

    public function histogramFacet(string $field, float $interval, array $options = []): self
    {
        $this->facets[$field] = array_merge([
            'field' => $field,
            'label' => ucfirst($field),
            'type' => 'histogram',
            'interval' => $interval,
            'missing' => true,
        ], $options);

        return $this;
    }

    public function statsFacet(string $field, array $options = []): self
    {
        $this->facets[$field] = array_merge([
            'field' => $field,
            'label' => ucfirst($field),
            'type' => 'stats',
            'missing' => true,
        ], $options);

        return $this;
    }

    public function cardinalityFacet(string $field, array $options = []): self
    {
        $this->facets[$field] = array_merge([
            'field' => $field,
            'label' => ucfirst($field),
            'type' => 'cardinality',
            'precision_threshold' => 3000,
            'missing' => true,
        ], $options);

        return $this;
    }

    public function filterFacet(string $facetName, callable $filter, array $options = []): self
    {
        $this->facets[$facetName] = array_merge([
            'name' => $facetName,
            'label' => ucfirst($facetName),
            'type' => 'filter',
            'filter' => $filter,
        ], $options);

        return $this;
    }

    public function addFacetFilter(string $field, mixed $value): self
    {
        if (!isset($this->facetFilters[$field])) {
            $this->facetFilters[$field] = [];
        }
        
        $this->facetFilters[$field][] = $value;
        
        return $this;
    }

    public function removeFacetFilter(string $field, mixed $value = null): self
    {
        if ($value === null) {
            unset($this->facetFilters[$field]);
        } else {
            $this->facetFilters[$field] = array_filter(
                $this->facetFilters[$field],
                fn($v) => $v !== $value
            );
            
            if (empty($this->facetFilters[$field])) {
                unset($this->facetFilters[$field]);
            }
        }
        
        return $this;
    }

    public function clearFacetFilters(): self
    {
        $this->facetFilters = [];
        return $this;
    }

    public function setMaxFacetValues(int $max): self
    {
        $this->maxFacetValues = $max;
        return $this;
    }

    public function getFacets(string $query = '', array $searchOptions = []): array
    {
        $searchOptions = array_merge($searchOptions, [
            'facets' => array_keys($this->facets),
            'facet_query_num_typos' => 2,
            'facet_query_tokens' => 'or',
        ]);

        // Add facet filters to search options
        if (!empty($this->facetFilters)) {
            $searchOptions['facet_filters'] = $this->buildFacetFilters();
        }

        $results = $this->searchManager->search($this->index, $query, $searchOptions);
        
        return $this->formatFacetResults($results);
    }

    public function getFacetValues(string $field, string $query = '', array $searchOptions = []): array
    {
        $facets = $this->getFacets($query, $searchOptions);
        return $facets[$field]['values'] ?? [];
    }

    public function getSelectedFacets(): array
    {
        $selected = [];
        
        foreach ($this->facetFilters as $field => $values) {
            foreach ($values as $value) {
                $selected[] = [
                    'field' => $field,
                    'value' => $value,
                    'label' => $this->getFacetLabel($field, $value),
                ];
            }
        }
        
        return $selected;
    }

    public function hasActiveFacets(): bool
    {
        return !empty($this->facetFilters);
    }

    public function getFacetUrl(string $field, mixed $value): string
    {
        $currentParams = app(Request::class)->query();
        $paramName = "facet_{$field}";
        
        if (!isset($currentParams[$paramName])) {
            $currentParams[$paramName] = [];
        } elseif (!is_array($currentParams[$paramName])) {
            $currentParams[$paramName] = [$currentParams[$paramName]];
        }
        
        $currentParams[$paramName][] = $value;
        
        return app(Request::class)->url() . '?' . http_build_query($currentParams);
    }

    public function getRemoveFacetUrl(string $field, mixed $value): string
    {
        $currentParams = app(Request::class)->query();
        $paramName = "facet_{$field}";
        
        if (isset($currentParams[$paramName])) {
            if (is_array($currentParams[$paramName])) {
                $currentParams[$paramName] = array_filter(
                    $currentParams[$paramName],
                    fn($v) => $v !== $value
                );
                
                if (empty($currentParams[$paramName])) {
                    unset($currentParams[$paramName]);
                }
            } else {
                unset($currentParams[$paramName]);
            }
        }
        
        return app(Request::class)->url() . '?' . http_build_query($currentParams);
    }

    public function getClearFacetsUrl(): string
    {
        $currentParams = app(Request::class)->query();
        
        // Remove all facet_* parameters
        foreach ($currentParams as $key => $value) {
            if (str_starts_with($key, 'facet_')) {
                unset($currentParams[$key]);
            }
        }
        
        return app(Request::class)->url() . '?' . http_build_query($currentParams);
    }

    public function fromRequest(): self
    {
        foreach (app(Request::class)->query() as $key => $value) {
            if (str_starts_with($key, 'facet_')) {
                $field = substr($key, 5); // Remove 'facet_' prefix
                
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $this->addFacetFilter($field, $v);
                    }
                } else {
                    $this->addFacetFilter($field, $value);
                }
            }
        }
        
        return $this;
    }

    private function buildFacetFilters(): array
    {
        $filters = [];
        
        foreach ($this->facetFilters as $field => $values) {
            $fieldFilters = [];
            
            foreach ($values as $value) {
                $fieldFilters[] = "{$field}:={$value}";
            }
            
            if (!empty($fieldFilters)) {
                $filters[] = '[' . implode(' || ', $fieldFilters) . ']';
            }
        }
        
        return $filters;
    }

    private function formatFacetResults(array $results): array
    {
        $facets = [];
        $adapterName = $this->searchManager->getCurrentAdapterName();
        
        foreach ($this->facets as $field => $config) {
            $facets[$field] = $this->formatFacet($field, $config, $results, $adapterName);
        }
        
        return $facets;
    }

    private function formatFacet(string $field, array $config, array $results, string $adapterName): array
    {
        $facetData = [
            'field' => $field,
            'label' => $config['label'],
            'type' => $config['type'],
            'values' => [],
            'total' => 0,
            'missing' => 0,
        ];

        return match ($adapterName) {
            'typesense' => $this->formatTypesenseFacet($field, $config, $results, $facetData),
            'meilisearch' => $this->formatMeilisearchFacet($field, $config, $results, $facetData),
            'tntsearch' => $this->formatTNTSearchFacet($field, $config, $results, $facetData),
            default => $facetData,
        };
    }

    private function formatTypesenseFacet(string $field, array $config, array $results, array $facetData): array
    {
        if (isset($results['facet_counts'][$field])) {
            $facetValues = $results['facet_counts'][$field];
            
            foreach ($facetValues as $value) {
                $facetData['values'][] = [
                    'value' => $value['value'],
                    'label' => $this->formatFacetValue($value['value'], $config),
                    'count' => $value['count'],
                    'selected' => in_array($value['value'], $this->facetFilters[$field] ?? []),
                    'url' => $this->getFacetUrl($field, $value['value']),
                    'remove_url' => $this->getRemoveFacetUrl($field, $value['value']),
                ];
            }
            
            $facetData['total'] = count($facetValues);
        }

        return $facetData;
    }

    private function formatMeilisearchFacet(string $field, array $config, array $results, array $facetData): array
    {
        if (isset($results['facetDistribution'][$field])) {
            $facetValues = $results['facetDistribution'][$field];
            
            foreach ($facetValues as $value => $count) {
                $facetData['values'][] = [
                    'value' => $value,
                    'label' => $this->formatFacetValue($value, $config),
                    'count' => $count,
                    'selected' => in_array($value, $this->facetFilters[$field] ?? []),
                    'url' => $this->getFacetUrl($field, $value),
                    'remove_url' => $this->getRemoveFacetUrl($field, $value),
                ];
            }
            
            $facetData['total'] = count($facetValues);
        }

        return $facetData;
    }

    private function formatTNTSearchFacet(string $field, array $config, array $results, array $facetData): array
    {
        // TNTSearch doesn't have built-in faceting
        // Would require custom implementation
        return $facetData;
    }

    private function formatFacetValue(mixed $value, array $config): string
    {
        if (isset($config['value_formatter']) && is_callable($config['value_formatter'])) {
            return $config['value_formatter']($value);
        }

        if ($config['type'] === 'date_histogram') {
            return date('M Y', strtotime($value));
        }

        if ($config['type'] === 'range') {
            return $this->formatRangeValue($value, $config);
        }

        return (string) $value;
    }

    private function formatRangeValue(mixed $value, array $config): string
    {
        if (is_array($value)) {
            $from = $value['from'] ?? '';
            $to = $value['to'] ?? '';
            
            if ($from && $to) {
                return "{$from} - {$to}";
            } elseif ($from) {
                return "≥ {$from}";
            } elseif ($to) {
                return "≤ {$to}";
            }
        }
        
        return (string) $value;
    }

    private function getFacetLabel(string $field, mixed $value): string
    {
        $config = $this->facets[$field] ?? [];
        return $this->formatFacetValue($value, $config);
    }

    public function renderFacets(string $query = '', array $searchOptions = []): string
    {
        $facets = $this->getFacets($query, $searchOptions);
        
        $html = '<div class="search-facets">';
        
        foreach ($facets as $field => $facet) {
            if (empty($facet['values'])) {
                continue;
            }
            
            $html .= '<div class="facet-group" data-facet="' . htmlspecialchars($field) . '">';
            $html .= '<h4>' . htmlspecialchars($facet['label']) . '</h4>';
            $html .= '<ul class="facet-values">';
            
            foreach ($facet['values'] as $value) {
                $class = $value['selected'] ? 'selected' : '';
                $html .= '<li class="facet-item ' . $class . '">';
                $html .= '<a href="' . htmlspecialchars($value['url']) . '">';
                $html .= '<span class="facet-label">' . htmlspecialchars($value['label']) . '</span>';
                $html .= '<span class="facet-count">(' . $value['count'] . ')</span>';
                $html .= '</a>';
                
                if ($value['selected']) {
                    $html .= '<a href="' . htmlspecialchars($value['remove_url']) . '" class="remove-facet">×</a>';
                }
                
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    public function renderSelectedFacets(): string
    {
        $selected = $this->getSelectedFacets();
        
        if (empty($selected)) {
            return '';
        }
        
        $html = '<div class="selected-facets">';
        $html .= '<h4>Active Filters:</h4>';
        $html .= '<ul>';
        
        foreach ($selected as $facet) {
            $url = $this->getRemoveFacetUrl($facet['field'], $facet['value']);
            $html .= '<li>';
            $html .= '<span>' . htmlspecialchars($facet['label']) . ': ' . htmlspecialchars($facet['value']) . '</span>';
            $html .= '<a href="' . htmlspecialchars($url) . '" class="remove-facet">×</a>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '<a href="' . htmlspecialchars($this->getClearFacetsUrl()) . '" class="clear-facets">Clear all</a>';
        $html .= '</div>';
        
        return $html;
    }
}
