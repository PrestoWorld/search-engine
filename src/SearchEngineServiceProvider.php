<?php

declare(strict_types=1);

namespace Prestoworld\SearchEngine;

use Prestoworld\SearchEngine\Config\SearchConfig;
use Prestoworld\SearchEngine\Contracts\SearchEngineInterface;
use App\Support\ServiceProvider;

class SearchEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchConfig::class, function ($app) {
            $config = config('search', []);
            return new SearchConfig($config);
        });

        $this->app->singleton(SearchManager::class, function ($app) {
            $config = $app->make(SearchConfig::class);
            return new SearchManager($config->all());
        });

        $this->app->bind(SearchEngineInterface::class, function ($app) {
            return $app->make(SearchManager::class)->getAdapter();
        });

        $this->app->alias(SearchManager::class, 'search');
    }

    public function boot(): void
    {
        if (PHP_SAPI === 'cli' && $this->app->has(\Witals\Framework\Console\Kernel::class)) {
            $kernel = $this->app->make(\Witals\Framework\Console\Kernel::class);
            $kernel->register(\Prestoworld\SearchEngine\Console\IndexCommand::class);
            $kernel->register(\Prestoworld\SearchEngine\Console\SearchCommand::class);
            $kernel->register(\Prestoworld\SearchEngine\Console\BenchmarkCommand::class);
        }
    }
}


