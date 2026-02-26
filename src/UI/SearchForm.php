<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\UI;

use Prestoworld\SearchEngine\Filters\FilterManager;
use Prestoworld\SearchEngine\Sorting\SortManager;
use Prestoworld\SearchEngine\QueryBuilder\SearchQueryBuilder;
use Witals\Framework\Http\Request;

class SearchForm
{
    private string $action;
    private string $method = 'GET';
    private array $attributes = [];
    private array $fields = [];
    private FilterManager $filterManager;
    private SortManager $sortManager;
    private ?string $queryField = 'q';
    private ?string $query = null;
    private array $hiddenFields = [];
    private bool $autoSubmit = false;
    private bool $resetButton = true;
    private array $cssClasses = [
        'form' => 'search-form',
        'input' => 'search-input',
        'select' => 'search-select',
        'button' => 'search-button',
        'reset' => 'search-reset',
        'filter' => 'search-filter',
        'sort' => 'search-sort',
    ];

    public function __construct(string $action = '', string $method = 'GET')
    {
        $this->action = $action ?: app(Request::class)->path();
        $this->filterManager = new FilterManager();
        $this->sortManager = new SortManager();
    }

    public static function create(string $action = '', string $method = 'GET'): self
    {
        return new self($action, $method);
    }

    public function action(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function method(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function attributes(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function addClass(string $class): self
    {
        $this->attributes['class'] = trim(($this->attributes['class'] ?? '') . ' ' . $class);
        return $this;
    }

    public function cssClasses(array $classes): self
    {
        $this->cssClasses = array_merge($this->cssClasses, $classes);
        return $this;
    }

    public function queryField(string $field, ?string $value = null): self
    {
        $this->queryField = $field;
        $this->query = $value ?? app(Request::class)->input($field);
        return $this;
    }

    public function textFilter(string $field, array $options = []): self
    {
        $this->fields[] = [
            'type' => 'text',
            'field' => $field,
            'label' => $options['label'] ?? ucfirst($field),
            'placeholder' => $options['placeholder'] ?? "Search {$field}...",
            'value' => $options['value'] ?? app(Request::class)->input($field),
            'attributes' => $options['attributes'] ?? [],
        ];

        $this->filterManager->text($field, $options['value'] ?? null);
        return $this;
    }

    public function selectFilter(string $field, array $options, array $config = []): self
    {
        $this->fields[] = [
            'type' => 'select',
            'field' => $field,
            'label' => $config['label'] ?? ucfirst($field),
            'options' => $options,
            'value' => $config['value'] ?? app(Request::class)->input($field),
            'placeholder' => $config['placeholder'] ?? 'Select...',
            'attributes' => $config['attributes'] ?? [],
        ];

        $this->filterManager->select($field, $config['value'] ?? null);
        return $this;
    }

    public function multiSelectFilter(string $field, array $options, array $config = []): self
    {
        $this->fields[] = [
            'type' => 'multiselect',
            'field' => $field,
            'label' => $config['label'] ?? ucfirst($field),
            'options' => $options,
            'value' => $config['value'] ?? app(Request::class)->input($field, []),
            'attributes' => $config['attributes'] ?? [],
        ];

        $this->filterManager->multiSelect($field, $config['value'] ?? null);
        return $this;
    }

    public function rangeFilter(string $field, array $config = []): self
    {
        $this->fields[] = [
            'type' => 'range',
            'field' => $field,
            'label' => $config['label'] ?? ucfirst($field),
            'min' => $config['min'] ?? app(Request::class)->input("{$field}_min"),
            'max' => $config['max'] ?? app(Request::class)->input("{$field}_max"),
            'min_placeholder' => $config['min_placeholder'] ?? 'Min',
            'max_placeholder' => $config['max_placeholder'] ?? 'Max',
            'attributes' => $config['attributes'] ?? [],
        ];

        $this->filterManager->range($field, $config['min'] ?? null, $config['max'] ?? null);
        return $this;
    }

    public function dateRangeFilter(string $field, array $config = []): self
    {
        $this->fields[] = [
            'type' => 'date_range',
            'field' => $field,
            'label' => $config['label'] ?? ucfirst($field),
            'min' => $config['min'] ?? app(Request::class)->input("{$field}_min"),
            'max' => $config['max'] ?? app(Request::class)->input("{$field}_max"),
            'min_placeholder' => $config['min_placeholder'] ?? 'From',
            'max_placeholder' => $config['max_placeholder'] ?? 'To',
            'attributes' => $config['attributes'] ?? [],
        ];

        $this->filterManager->dateRange($field, $config['min'] ?? null, $config['max'] ?? null);
        return $this;
    }

    public function checkboxFilter(string $field, array $config = []): self
    {
        $this->fields[] = [
            'type' => 'checkbox',
            'field' => $field,
            'label' => $config['label'] ?? ucfirst($field),
            'value' => $config['value'] ?? app(Request::class)->input($field),
            'checked' => $config['checked'] ?? (bool) app(Request::class)->input($field),
            'attributes' => $config['attributes'] ?? [],
        ];

        $this->filterManager->boolean($field, $config['checked'] ?? null);
        return $this;
    }

    public function sortSelect(array $options, array $config = []): self
    {
        $this->fields[] = [
            'type' => 'sort',
            'field' => 'sort',
            'label' => $config['label'] ?? 'Sort by',
            'options' => $options,
            'value' => $config['value'] ?? app(Request::class)->input('sort'),
            'attributes' => $config['attributes'] ?? [],
        ];

        $this->sortManager->fromRequest();
        return $this;
    }

    public function hiddenField(string $name, mixed $value): self
    {
        $this->hiddenFields[$name] = $value;
        return $this;
    }

    public function autoSubmit(bool $enabled = true): self
    {
        $this->autoSubmit = $enabled;
        return $this;
    }

    public function resetButton(bool $show = true): self
    {
        $this->resetButton = $show;
        return $this;
    }

    public function render(): string
    {
        return $this->buildForm();
    }

    public function toHtml(): string
    {
        return $this->render()->toHtml();
    }

    public function getFilterManager(): FilterManager
    {
        return $this->filterManager;
    }

    public function getSortManager(): SortManager
    {
        return $this->sortManager;
    }

    public function applyToQueryBuilder(SearchQueryBuilder $queryBuilder): SearchQueryBuilder
    {
        // Apply filters
        $this->filterManager->applyTo(function ($field, $operator, $value) use ($queryBuilder) {
            match ($operator) {
                '=' => $queryBuilder->where($field, $value),
                '!=' => $queryBuilder->where($field, '!=', $value),
                '>' => $queryBuilder->where($field, '>', $value),
                '>=' => $queryBuilder->where($field, '>=', $value),
                '<' => $queryBuilder->where($field, '<', $value),
                '<=' => $queryBuilder->where($field, '<=', $value),
                'LIKE' => $queryBuilder->whereLike($field, $value),
                'IN' => $queryBuilder->whereIn($field, (array) $value),
                'NOT IN' => $queryBuilder->whereNotIn($field, (array) $value),
                'BETWEEN' => $queryBuilder->whereBetween($field, $value[0], $value[1]),
                'NULL' => $queryBuilder->whereNull($field),
                'NOT NULL' => $queryBuilder->whereNotNull($field),
                default => $queryBuilder->where($field, $operator, $value),
            };
        });

        // Apply sorting
        $this->sortManager->applyTo(function ($field, $direction, $options = []) use ($queryBuilder) {
            $queryBuilder->orderBy($field, $direction);
        });

        // Apply text query
        if ($this->query) {
            $queryBuilder->query($this->query);
        }

        return $queryBuilder;
    }

    private function buildForm(): string
    {
        $html = '<form';
        $html .= ' action="' . htmlspecialchars($this->action) . '"';
        $html .= ' method="' . htmlspecialchars($this->method) . '"';
        
        foreach ($this->attributes as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
        }
        
        $html .= '>';

        // Query input field
        if ($this->queryField) {
            $html .= $this->buildQueryField();
        }

        // Filter fields
        foreach ($this->fields as $field) {
            $html .= $this->buildField($field);
        }

        // Hidden fields
        foreach ($this->hiddenFields as $name => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars((string) $value) . '">';
        }

        // Buttons
        $html .= '<div class="form-actions">';
        $html .= '<button type="submit" class="' . $this->cssClasses['button'] . '">Search</button>';
        
        if ($this->resetButton) {
            $html .= '<button type="reset" class="' . $this->cssClasses['reset'] . '">Reset</button>';
        }
        
        $html .= '</div>';

        $html .= '</form>';

        if ($this->autoSubmit) {
            $html .= '<script>';
            $html .= 'document.addEventListener("DOMContentLoaded", function() {';
            $html .= '  const form = document.querySelector(".' . $this->cssClasses['form'] . '");';
            $html .= '  if (form) {';
            $html .= '    form.addEventListener("change", function(e) {';
            $html .= '      if (e.target.tagName === "SELECT" || e.target.type === "checkbox") {';
            $html .= '        form.submit();';
            $html .= '      }';
            $html .= '    });';
            $html .= '  }';
            $html .= '});';
            $html .= '</script>';
        }

        return $html;
    }

    private function buildQueryField(): string
    {
        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($this->queryField) . '">Search</label>';
        $html .= '<input type="text" name="' . htmlspecialchars($this->queryField) . '"';
        $html .= ' id="' . htmlspecialchars($this->queryField) . '"';
        $html .= ' class="' . $this->cssClasses['input'] . '"';
        $html .= ' value="' . htmlspecialchars($this->query ?? '') . '"';
        $html .= ' placeholder="Search...">';
        $html .= '</div>';
        
        return $html;
    }

    private function buildField(array $field): string
    {
        return match ($field['type']) {
            'text' => $this->buildTextField($field),
            'select' => $this->buildSelectField($field),
            'multiselect' => $this->buildMultiSelectField($field),
            'range' => $this->buildRangeField($field),
            'date_range' => $this->buildDateRangeField($field),
            'checkbox' => $this->buildCheckboxField($field),
            'sort' => $this->buildSortField($field),
            default => '',
        };
    }

    private function buildTextField(array $field): string
    {
        $html = '<div class="form-group ' . $this->cssClasses['filter'] . '">';
        $html .= '<label for="' . htmlspecialchars($field['field']) . '">' . htmlspecialchars($field['label']) . '</label>';
        $html .= '<input type="text" name="' . htmlspecialchars($field['field']) . '"';
        $html .= ' id="' . htmlspecialchars($field['field']) . '"';
        $html .= ' class="' . $this->cssClasses['input'] . '"';
        $html .= ' value="' . htmlspecialchars($field['value'] ?? '') . '"';
        $html .= ' placeholder="' . htmlspecialchars($field['placeholder']) . '"';
        
        foreach ($field['attributes'] as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
        }
        
        $html .= '></div>';
        
        return $html;
    }

    private function buildSelectField(array $field): string
    {
        $html = '<div class="form-group ' . $this->cssClasses['filter'] . '">';
        $html .= '<label for="' . htmlspecialchars($field['field']) . '">' . htmlspecialchars($field['label']) . '</label>';
        $html .= '<select name="' . htmlspecialchars($field['field']) . '"';
        $html .= ' id="' . htmlspecialchars($field['field']) . '"';
        $html .= ' class="' . $this->cssClasses['select'] . '"';
        
        foreach ($field['attributes'] as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
        }
        
        $html .= '>';
        
        if (isset($field['placeholder'])) {
            $html .= '<option value="">' . htmlspecialchars($field['placeholder']) . '</option>';
        }
        
        foreach ($field['options'] as $value => $label) {
            $selected = ($field['value'] ?? '') == (string) $value ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars((string) $value) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        
        $html .= '</select></div>';
        
        return $html;
    }

    private function buildMultiSelectField(array $field): string
    {
        $html = '<div class="form-group ' . $this->cssClasses['filter'] . '">';
        $html .= '<label>' . htmlspecialchars($field['label']) . '</label>';
        
        foreach ($field['options'] as $value => $label) {
            $checked = is_array($field['value']) && in_array((string) $value, $field['value']) ? 'checked' : '';
            $html .= '<label class="checkbox-label">';
            $html .= '<input type="checkbox" name="' . htmlspecialchars($field['field']) . '[]"';
            $html .= ' value="' . htmlspecialchars((string) $value) . '" ' . $checked;
            $html .= ' class="' . $this->cssClasses['input'] . '">';
            $html .= ' ' . htmlspecialchars($label);
            $html .= '</label>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    private function buildRangeField(array $field): string
    {
        $html = '<div class="form-group ' . $this->cssClasses['filter'] . '">';
        $html .= '<label>' . htmlspecialchars($field['label']) . '</label>';
        $html .= '<div class="range-inputs">';
        $html .= '<input type="text" name="' . htmlspecialchars($field['field']) . '_min"';
        $html .= ' placeholder="' . htmlspecialchars($field['min_placeholder']) . '"';
        $html .= ' value="' . htmlspecialchars((string) ($field['min'] ?? '')) . '"';
        $html .= ' class="' . $this->cssClasses['input'] . '">';
        $html .= '<span>to</span>';
        $html .= '<input type="text" name="' . htmlspecialchars($field['field']) . '_max"';
        $html .= ' placeholder="' . htmlspecialchars($field['max_placeholder']) . '"';
        $html .= ' value="' . htmlspecialchars((string) ($field['max'] ?? '')) . '"';
        $html .= ' class="' . $this->cssClasses['input'] . '">';
        $html .= '</div></div>';
        
        return $html;
    }

    private function buildDateRangeField(array $field): string
    {
        $html = '<div class="form-group ' . $this->cssClasses['filter'] . '">';
        $html .= '<label>' . htmlspecialchars($field['label']) . '</label>';
        $html .= '<div class="date-range-inputs">';
        $html .= '<input type="date" name="' . htmlspecialchars($field['field']) . '_min"';
        $html .= ' placeholder="' . htmlspecialchars($field['min_placeholder']) . '"';
        $html .= ' value="' . htmlspecialchars((string) ($field['min'] ?? '')) . '"';
        $html .= ' class="' . $this->cssClasses['input'] . '">';
        $html .= '<span>to</span>';
        $html .= '<input type="date" name="' . htmlspecialchars($field['field']) . '_max"';
        $html .= ' placeholder="' . htmlspecialchars($field['max_placeholder']) . '"';
        $html .= ' value="' . htmlspecialchars((string) ($field['max'] ?? '')) . '"';
        $html .= ' class="' . $this->cssClasses['input'] . '">';
        $html .= '</div></div>';
        
        return $html;
    }

    private function buildCheckboxField(array $field): string
    {
        $checked = $field['checked'] ? 'checked' : '';
        $html = '<div class="form-group ' . $this->cssClasses['filter'] . '">';
        $html .= '<label class="checkbox-label">';
        $html .= '<input type="checkbox" name="' . htmlspecialchars($field['field']) . '"';
        $html .= ' value="1" ' . $checked;
        $html .= ' class="' . $this->cssClasses['input'] . '">';
        $html .= ' ' . htmlspecialchars($field['label']);
        $html .= '</label></div>';
        
        return $html;
    }

    private function buildSortField(array $field): string
    {
        $html = '<div class="form-group ' . $this->cssClasses['sort'] . '">';
        $html .= '<label for="' . htmlspecialchars($field['field']) . '">' . htmlspecialchars($field['label']) . '</label>';
        $html .= '<select name="' . htmlspecialchars($field['field']) . '"';
        $html .= ' id="' . htmlspecialchars($field['field']) . '"';
        $html .= ' class="' . $this->cssClasses['select'] . '"';
        
        foreach ($field['attributes'] as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
        }
        
        $html .= '>';
        
        foreach ($field['options'] as $value => $label) {
            $selected = ($field['value'] ?? '') == (string) $value ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars((string) $value) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        
        $html .= '</select></div>';
        
        return $html;
    }
}
