<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine;

/**
 * Modern Search Result DTO
 */
class SearchResult
{
    protected array $items;
    protected int $total;

    public function __construct(array $items, int $total)
    {
        $this->items = $items;
        $this->total = $total;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
