# PrestoWorld Search Engine - Installation Guide

## 📋 Prerequisites

- PHP 8.1 or higher
- Witals Framework
- Composer
- One of the supported search engines:
  - TNTSearch (file-based, no additional requirements)
  - Typesense (requires Typesense server)
  - Meilisearch (requires Meilisearch server)

## 🚀 Installation

### 1. Install Package

```bash
composer require prestoworld/search-engine
```

### 2. Service Provider Registration

The Witals framework will automatically detect and load the module if it's placed in the `vendor` or `modules` directory. Ensure `Prestoworld\SearchEngine\SearchEngineServiceProvider::class` is registered in your application's provider list if necessary.

### 3. Configure Environment

Add the following to your `.env` file:

```env
# Choose your default search engine
SEARCH_ENGINE=meilisearch

# General settings
SEARCH_CACHE_ENABLED=true
SEARCH_BATCH_SIZE=1000
SEARCH_DEFAULT_LIMIT=10
SEARCH_MAX_LIMIT=100
```

### 4. Configure Your Search Engine

#### For TNTSearch (File-based)

```env
SEARCH_ENGINE=tntsearch
SEARCH_STORAGE_PATH=storage/app/search
```

#### For Typesense

```env
SEARCH_ENGINE=typesense
TYPESENSE_API_KEY=your_api_key_here
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108
TYPESENSE_PROTOCOL=http
```

#### For Meilisearch

```env
SEARCH_ENGINE=meilisearch
MEILISEARCH_URL=http://localhost:7700
MEILISEARCH_API_KEY=your_master_key_here
```

## 🔧 Search Engine Setup

### TNTSearch Setup

TNTSearch requires no additional setup - it's file-based and works out of the box.

### Typesense Setup

1. **Install Typesense**:

```bash
# Using Docker (recommended)
docker run -d --name typesense \
  -p 8108:8108 \
  -v /typesense-data:/data \
  typesense/typesense:latest \
  --data-dir /data \
  --api-key=your_api_key_here \
  --enable-cors
```

2. **Verify Installation**:

```bash
curl http://localhost:8108/health
```

### Meilisearch Setup

1. **Install Meilisearch**:

```bash
# Using Docker (recommended)
docker run -d --name meilisearch \
  -p 7700:7700 \
  -v /meili-data:/meili_data \
  getmeili/meilisearch:latest \
  --master-key=your_master_key_here
```

2. **Verify Installation**:

```bash
curl http://localhost:7700/health
```

## 📁 Directory Structure

After installation, the search configuration is managed via the framework's configuration system.

```
storage/app/
└── search/                   # TNTSearch index files (if using TNTSearch)
```

## 🎯 Quick Test

### 1. Index Your Data

Use the Witals console to index data:

```bash
php witals search:index test --source="App\Models\Post"
```

### 2. Search from Code

```php
use Prestoworld\SearchEngine\SearchManager;

class SearchController
{
    public function search()
    {
        $searchManager = app(SearchManager::class);
        $results = $searchManager->search('test', 'query');
        
        return $results;
    }
}
```

## 🔍 Verification Commands

### Test Performance

```bash
php witals search:benchmark test "query" --iterations=100
```

## ⚙️ Configuration Options

Configuration is typically handled via `config/search.php` in the application or the module's own config.

```php
return [
    'default_adapter' => env('SEARCH_ENGINE', 'tntsearch'),
    
    'adapters' => [
        'tntsearch' => [
            'storage_path' => storage_path('app/search'),
            'searchable_fields' => ['title', 'content'],
            'fuzziness' => false,
        ],
        // ... other adapters
    ],
    
    // ...
];
```

## 🚨 Common Issues & Solutions

### 1. Typesense Connection Error
- Verify Typesense is running: `curl http://localhost:8108/health`
- Check API key in `.env`

### 2. Meilisearch Connection Error
- Verify Meilisearch is running: `curl http://localhost:7700/health`
- Check master key in `.env`

### 3. TNTSearch Permission Error
```bash
chmod -R 755 storage/app/search
```

## 🔧 Development Setup

1. **Clone the repository**:
```bash
git clone https://github.com/prestoworld/search-engine.git
cd search-engine
```

2. **Install dependencies**:
```bash
composer install
```

3. **Run tests**:
```bash
vendor/bin/phpunit
```

---

**Happy Searching! 🚀**
