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

test('only the four approved Livewire components exist and they do not query persistence directly', function () {
    $approved = [
        'Modules/Dashboard/app/Livewire/QuickTaskCreate.php',
        'Modules/Tasks/app/Livewire/TaskCommentForm.php',
        'Modules/Tasks/app/Livewire/TaskFilters.php',
        'Modules/Tasks/app/Livewire/TaskStatusSelector.php',
    ];
    $found = array_keys(array_filter(sourceFiles('Modules'), fn (string $source, string $path): bool => str_contains($path, '/app/Livewire/'), ARRAY_FILTER_USE_BOTH));
    sort($approved);
    sort($found);

    expect(array_map(fn (string $path): string => str_replace(base_path().'/', '', $path), $found))->toBe($approved);

    foreach ($found as $path) {
        $source = file_get_contents($path);

        expect($source, basename($path))->not->toMatch('/::query\(|\bDB::|\bStorage::|Repositories?\\\\/');
    }
});

test('request input and task media boundaries stay in their approved layers', function () {
    $requestAll = [];

    foreach (array_merge(sourceFiles('app'), sourceFiles('Modules')) as $path => $source) {
        if (str_contains($source, 'request()->all(')) {
            $requestAll[] = str_replace(base_path().'/', '', $path);
        }
    }

    expect($requestAll)->toBe([]);

    $taskSources = sourceFiles('Modules/Tasks/app');
    $storageViolations = [];

    foreach ($taskSources as $path => $source) {
        if (str_contains($path, '/Support/TaskAttachmentMediaBackfill.php')) {
            continue; // One-time legacy backfill; runtime media I/O belongs to Media.
        }

        if (preg_match('/\bStorage::|->store(?:As)?\(/', $source, $match)) {
            $storageViolations[] = str_replace(base_path().'/', '', $path).': '.$match[0];
        }
    }

    expect($storageViolations)->toBe([]);
});

test('module routes and API controllers retain module ownership and resource responses', function () {
    $routeViolations = [];

    foreach (sourceFiles('routes') as $path => $source) {
        if (str_contains($source, "Route::prefix('api/v1')") || str_contains($source, 'Route::prefix("api/v1")')) {
            $routeViolations[] = str_replace(base_path().'/', '', $path);
        }
    }

    expect($routeViolations)->toBe([]);

    foreach (sourceFiles('Modules') as $path => $source) {
        if (! str_contains($path, '/Http/Controllers/Api/V1/')) {
            continue;
        }

        expect($source, basename($path))->toMatch('/Http\\\\Resources\\\\|Resource::/');
    }
});

test('the executable Pest suite contains no skipped tests', function () {
    $skips = [];

    foreach (array_merge(sourceFiles('tests'), sourceFiles('Modules')) as $path => $source) {
        if (! str_contains($path, '/tests/')) {
            continue;
        }

        if (preg_match('/(?:->skip\(|\bskip\()/', $source, $match)) {
            $skips[] = str_replace(base_path().'/', '', $path).': '.$match[0];
        }
    }

    expect($skips)->toBe([]);
});

test('enabled modules expose their documented repository bindings', function () {
    $bindings = [
        \Modules\Projects\Repositories\ProjectRepository::class,
        \Modules\Projects\Repositories\ProjectMemberRepository::class,
        \Modules\Tasks\Repositories\TaskRepository::class,
        \Modules\Tasks\Repositories\TaskCommentRepository::class,
        \Modules\Tasks\Repositories\TaskAttachmentRepository::class,
        \Modules\Tasks\Repositories\TaskLabelRepository::class,
        \Modules\Tasks\Repositories\TaskWatcherRepository::class,
        \Modules\Media\Repositories\MediaRepository::class,
    ];

    foreach ($bindings as $binding) {
        expect(app()->bound($binding), $binding)->toBeTrue();
    }
});

test('cross-module imports stay within the documented direct-dependency graph', function () {
    $allowed = [
        'Projects' => ['Activity', 'Tasks'],
        'Tasks' => ['Projects', 'Media', 'Activity'],
        'Activity' => ['Projects', 'Tasks'],
        'Dashboard' => ['Projects', 'Tasks', 'Activity'],
        'Media' => [],
    ];
    $violations = [];

    foreach ($allowed as $module => $dependencies) {
        foreach (sourceFiles("Modules/{$module}/app") as $path => $source) {
            preg_match_all('/use Modules\\\\([A-Za-z]+)\\\\/', $source, $matches);
            foreach (array_unique($matches[1]) as $dependency) {
                if ($dependency !== $module && ! in_array($dependency, $dependencies, true)) {
                    $violations[] = str_replace(base_path().'/', '', $path).": {$module} -> {$dependency}";
                }
            }
        }
    }

    expect($violations)->toBe([]);
});
