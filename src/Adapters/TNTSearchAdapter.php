<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine\Adapters;

use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use TeamTNT\TNTSearch\TNTSearch;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;

class TNTSearchAdapter implements SearchEngineInterface
{
    private TNTSearch $tnt;
    private array $config;
    private string $storagePath;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->storagePath = $config['storage_path'] ?? storage_path('app/search');
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->tnt = new TNTSearch();
        $this->tnt->loadConfig([
            'storage'   => $this->storagePath,
            'driver'    => $this->config['driver'] ?? 'sqlite',
            'host'      => $this->config['host'] ?? 'localhost',
            'database'  => $this->config['database'] ?? '',
            'username'  => $this->config['username'] ?? '',
            'password'  => $this->config['password'] ?? '',
            'port'      => $this->config['port'] ?? 3306,
            'charset'   => $this->config['charset'] ?? 'utf8mb4',
        ]);

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function index(string $index, array $documents): void
    {
        $indexer = $this->tnt->createIndex("{$index}.index");
        
        foreach ($documents as $document) {
            $id = $document['id'] ?? uniqid();
            $content = $this->prepareDocumentContent($document);
            $indexer->insert(['id' => $id, 'content' => $content]);
        }
    }

    public function addDocument(string $index, array $document): void
    {
        if (!$this->indexExists($index)) {
            $this->index($index, [$document]);
            return;
        }

        $this->tnt->selectIndex("{$index}.index");
        $id = $document['id'] ?? uniqid();
        $content = $this->prepareDocumentContent($document);
        
        $this->tnt->insert(['id' => $id, 'content' => $content]);
    }

    public function updateDocument(string $index, string $id, array $document): void
    {
        $this->deleteDocument($index, $id);
        $document['id'] = $id;
        $this->addDocument($index, $document);
    }

    public function deleteDocument(string $index, string $id): void
    {
        if (!$this->indexExists($index)) {
            return;
        }

        $this->tnt->selectIndex("{$index}.index");
        $this->tnt->delete($id);
    }

    public function search(string $index, string $query, array $options = []): array
    {
        if (!$this->indexExists($index)) {
            return [];
        }

        $this->tnt->selectIndex("{$index}.index");
        
        $limit = $options['limit'] ?? 10;
        $fuzziness = $options['fuzziness'] ?? false;
        
        if ($fuzziness) {
            $this->tnt->fuzziness = true;
        }

        $results = $this->tnt->search($query, $limit);
        
        return $this->formatSearchResults($results);
    }

    public function getDocument(string $index, string $id): ?array
    {
        // TNTSearch doesn't have direct getDocument method
        // This would require custom implementation
        return null;
    }

    public function deleteIndex(string $index): void
    {
        $indexPath = $this->storagePath . "/{$index}.index";
        if (file_exists($indexPath)) {
            unlink($indexPath);
        }
    }

    public function indexExists(string $index): bool
    {
        return file_exists($this->storagePath . "/{$index}.index");
    }

    public function getName(): string
    {
        return 'tntsearch';
    }

    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initialize();
    }

    private function prepareDocumentContent(array $document): string
    {
        $fields = $this->config['searchable_fields'] ?? ['title', 'content'];
        $content = [];
        
        foreach ($fields as $field) {
            if (isset($document[$field])) {
                $content[] = $document[$field];
            }
        }
        
        return implode(' ', $content);
    }

    private function formatSearchResults(array $results): array
    {
        $formatted = [];
        
        foreach ($results['ids'] as $key => $id) {
            $formatted[] = [
                'id' => $id,
                'score' => $results['scores'][$key] ?? 0,
                'document' => $results['documents'][$key] ?? [],
            ];
        }
        
        return $formatted;
    }
}
