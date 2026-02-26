<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Filters;

use Witals\Framework\Http\Request;

class FilterManager
{
    private array $filters = [];
    private array $availableFilters = [];
    private ?Request $request = null;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? app(Request::class);
    }

    public function register(string $name, callable $filter): self
    {
        $this->availableFilters[$name] = $filter;
        return $this;
    }

    public function text(string $field, ?string $value = null): self
    {
        $this->filters[] = [
            'type' => 'text',
            'field' => $field,
            'value' => $value ?? $this->request->input($field),
            'operator' => 'LIKE'
        ];
        return $this;
    }

    public function exact(string $field, mixed $value = null): self
    {
        $this->filters[] = [
            'type' => 'exact',
            'field' => $field,
            'value' => $value ?? $this->request->input($field),
            'operator' => '='
        ];
        return $this;
    }

    public function select(string $field, mixed $value = null): self
    {
        $this->filters[] = [
            'type' => 'select',
            'field' => $field,
            'value' => $value ?? $this->request->input($field),
            'operator' => '='
        ];
        return $this;
    }

    public function multiSelect(string $field, array $values = null): self
    {
        $values = $values ?? $this->request->input($field, []);
        
        if (!is_array($values)) {
            $values = explode(',', $values);
        }

        $this->filters[] = [
            'type' => 'multiselect',
            'field' => $field,
            'value' => array_filter($values),
            'operator' => 'IN'
        ];
        return $this;
    }

    public function range(string $field, mixed $min = null, mixed $max = null): self
    {
        $min = $min ?? $this->request->input("{$field}_min");
        $max = $max ?? $this->request->input("{$field}_max");

        if ($min !== null || $max !== null) {
            $this->filters[] = [
                'type' => 'range',
                'field' => $field,
                'min' => $min,
                'max' => $max,
                'operator' => 'BETWEEN'
            ];
        }
        return $this;
    }

    public function dateRange(string $field, ?string $min = null, ?string $max = null): self
    {
        $min = $min ?? $this->request->input("{$field}_min");
        $max = $max ?? $this->request->input("{$field}_max");

        if ($min !== null || $max !== null) {
            $this->filters[] = [
                'type' => 'date_range',
                'field' => $field,
                'min' => $min,
                'max' => $max,
                'operator' => 'BETWEEN'
            ];
        }
        return $this;
    }

    public function date(string $field, ?string $date = null): self
    {
        $this->filters[] = [
            'type' => 'date',
            'field' => $field,
            'value' => $date ?? $this->request->input($field),
            'operator' => '='
        ];
        return $this;
    }

    public function boolean(string $field, ?bool $value = null): self
    {
        $value = $value ?? (bool) $this->request->input($field);
        
        if ($value !== null) {
            $this->filters[] = [
                'type' => 'boolean',
                'field' => $field,
                'value' => $value,
                'operator' => '='
            ];
        }
        return $this;
    }

    public function exists(string $field, ?bool $exists = null): self
    {
        $exists = $exists ?? $this->request->input("{$field}_exists");
        
        if ($exists !== null) {
            $this->filters[] = [
                'type' => 'exists',
                'field' => $field,
                'value' => $exists,
                'operator' => $exists ? 'NOT NULL' : 'NULL'
            ];
        }
        return $this;
    }

    public function custom(string $name, mixed ...$args): self
    {
        if (isset($this->availableFilters[$name])) {
            $filter = $this->availableFilters[$name];
            $result = $filter(...$args);
            
            if (is_array($result)) {
                $this->filters[] = $result;
            }
        }
        
        return $this;
    }

    public function applyTo(callable $builder): void
    {
        foreach ($this->filters as $filter) {
            $this->applyFilter($builder, $filter);
        }
    }

    public function toArray(): array
    {
        return $this->filters;
    }

    public function toQueryParams(): array
    {
        $params = [];
        
        foreach ($this->filters as $filter) {
            switch ($filter['type']) {
                case 'text':
                case 'exact':
                case 'select':
                case 'date':
                case 'boolean':
                    if ($filter['value'] !== null) {
                        $params[$filter['field']] = $filter['value'];
                    }
                    break;
                    
                case 'multiselect':
                    if (!empty($filter['value'])) {
                        $params[$filter['field']] = implode(',', $filter['value']);
                    }
                    break;
                    
                case 'range':
                case 'date_range':
                    if ($filter['min'] !== null) {
                        $params["{$filter['field']}_min"] = $filter['min'];
                    }
                    if ($filter['max'] !== null) {
                        $params["{$filter['field']}_max"] = $filter['max'];
                    }
                    break;
                    
                case 'exists':
                    $params["{$filter['field']}_exists"] = $filter['value'];
                    break;
            }
        }
        
        return $params;
    }

    public function hasActiveFilters(): bool
    {
        foreach ($this->filters as $filter) {
            switch ($filter['type']) {
                case 'range':
                case 'date_range':
                    if ($filter['min'] !== null || $filter['max'] !== null) {
                        return true;
                    }
                    break;
                    
                case 'multiselect':
                    if (!empty($filter['value'])) {
                        return true;
                    }
                    break;
                    
                default:
                    if ($filter['value'] !== null) {
                        return true;
                    }
                    break;
            }
        }
        
        return false;
    }

    public function getActiveFilters(): array
    {
        return array_filter($this->filters, function ($filter) {
            switch ($filter['type']) {
                case 'range':
                case 'date_range':
                    return $filter['min'] !== null || $filter['max'] !== null;
                    
                case 'multiselect':
                    return !empty($filter['value']);
                    
                default:
                    return $filter['value'] !== null;
            }
        });
    }

    public function clear(): self
    {
        $this->filters = [];
        return $this;
    }

    public function remove(string $field): self
    {
        $this->filters = array_filter($this->filters, function ($filter) use ($field) {
            return $filter['field'] !== $field;
        });
        
        return $this;
    }

    private function applyFilter(callable $builder, array $filter): void
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'] ?? null;

        switch ($filter['type']) {
            case 'text':
                $builder($field, 'LIKE', "%{$value}%");
                break;
                
            case 'exact':
            case 'select':
            case 'date':
            case 'boolean':
                $builder($field, $operator, $value);
                break;
                
            case 'multiselect':
                if (!empty($value)) {
                    $builder($field, 'IN', $value);
                }
                break;
                
            case 'range':
            case 'date_range':
                $min = $filter['min'] ?? null;
                $max = $filter['max'] ?? null;
                
                if ($min !== null && $max !== null) {
                    $builder($field, 'BETWEEN', [$min, $max]);
                } elseif ($min !== null) {
                    $builder($field, '>=', $min);
                } elseif ($max !== null) {
                    $builder($field, '<=', $max);
                }
                break;
                
            case 'exists':
                $builder($field, $operator, null);
                break;
        }
    }

    public static function fromRequest(?Request $request = null): self
    {
        return new self($request);
    }

    public function getFilterSummary(): array
    {
        $summary = [];
        
        foreach ($this->getActiveFilters() as $filter) {
            $label = $this->getFilterLabel($filter);
            if ($label) {
                $summary[] = $label;
            }
        }
        
        return $summary;
    }

    private function getFilterLabel(array $filter): ?string
    {
        $field = $filter['field'];
        
        switch ($filter['type']) {
            case 'text':
            case 'exact':
            case 'select':
                return "{$field}: {$filter['value']}";
                
            case 'multiselect':
                if (!empty($filter['value'])) {
                    return "{$field}: " . implode(', ', $filter['value']);
                }
                break;
                
            case 'range':
                $parts = [];
                if ($filter['min'] !== null) {
                    $parts[] = "≥ {$filter['min']}";
                }
                if ($filter['max'] !== null) {
                    $parts[] = "≤ {$filter['max']}";
                }
                return $field . ': ' . implode(' and ', $parts);
                
            case 'date_range':
                $parts = [];
                if ($filter['min'] !== null) {
                    $parts[] = "from {$filter['min']}";
                }
                if ($filter['max'] !== null) {
                    $parts[] = "to {$filter['max']}";
                }
                return $field . ': ' . implode(' ', $parts);
                
            case 'date':
                return "{$field}: {$filter['value']}";
                
            case 'boolean':
                return "{$field}: " . ($filter['value'] ? 'Yes' : 'No');
                
            case 'exists':
                return "{$field}: " . ($filter['value'] ? 'Exists' : 'Not exists');
        }
        
        return null;
    }
}
