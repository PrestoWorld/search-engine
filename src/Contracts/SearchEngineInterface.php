<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Contracts;

interface SearchEngineInterface
{
    /**
     * Create or update index
     */
    public function index(string $index, array $documents): void;

    /**
     * Add document to index
     */
    public function addDocument(string $index, array $document): void;

    /**
     * Update document in index
     */
    public function updateDocument(string $index, string $id, array $document): void;

    /**
     * Delete document from index
     */
    public function deleteDocument(string $index, string $id): void;

    /**
     * Search documents
     */
    public function search(string $index, string $query, array $options = []): array;

    /**
     * Get document by ID
     */
    public function getDocument(string $index, string $id): ?array;

    /**
     * Delete entire index
     */
    public function deleteIndex(string $index): void;

    /**
     * Check if index exists
     */
    public function indexExists(string $index): bool;

    /**
     * Get search engine name
     */
    public function getName(): string;

    /**
     * Configure the search engine
     */
    public function configure(array $config): void;
}
