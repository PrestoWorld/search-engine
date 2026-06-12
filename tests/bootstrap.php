<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';

use Witals\Framework\Container\Container;
use Witals\Framework\Http\Request;

// Test-compatible request stub
class TestRequest extends Request
{
    private array $inputData = [];
    private array $queryData = [];
    private string $pathData = '/';

    public function __construct()
    {
        parent::__construct('GET', '/', [], [], [], [], [], [], null);
    }

    public function setTestInput(string $key, mixed $value): void
    {
        $this->inputData[$key] = $value;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->inputData;
        }
        return $this->inputData[$key] ?? $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryData;
        }
        return $this->queryData[$key] ?? $default;
    }

    public function path(): string
    {
        return $this->pathData;
    }
}

// Create a test container with Application-like methods
$testContainer = new class extends Container {
    public function storagePath(string $path = ''): string
    {
        return sys_get_temp_dir() . '/prestoworld-test/storage' . ($path ? '/' . $path : '');
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function getErrorLogPath(): string
    {
        return sys_get_temp_dir() . '/prestoworld-test/error.log';
    }

    public function isLongRunning(): bool
    {
        return false;
    }

    public function basePath(): string
    {
        return sys_get_temp_dir() . '/prestoworld-test';
    }
};

$request = new TestRequest();
$testContainer->instance(Request::class, $request);
$testContainer->instance(Container::class, $testContainer);
Container::setInstance($testContainer);
