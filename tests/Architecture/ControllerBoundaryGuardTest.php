<?php

uses(Tests\TestCase::class);

function sourceFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory)));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[$file->getPathname()] = file_get_contents($file->getPathname());
        }
    }

    return $files;
}

test('controllers do not execute Eloquent DB or Storage queries', function () {
    $violations = [];

    foreach (array_merge(sourceFiles('app/Http/Controllers'), sourceFiles('Modules')) as $path => $source) {
        if (! str_contains($path, '/Http/Controllers/')) {
            continue;
        }

        if (preg_match('/\b[A-Z][A-Za-z0-9_]*::query\(|\bDB::|\bStorage::/', $source, $match)) {
            $violations[] = basename($path).': '.$match[0];
        }
    }

    expect($violations)->toBe([]);
});

test('the application does not map every LogicException to a conflict response', function () {
    $source = file_get_contents(base_path('bootstrap/app.php'));

    expect($source)->not->toMatch('/render\(function \(LogicException .*\).*409/s');
});
