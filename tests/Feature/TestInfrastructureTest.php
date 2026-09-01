<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

test('the default suite uses isolated sqlite services and fake local storage', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('cache.default'))->toBe('array')
        ->and(config('session.driver'))->toBe('array')
        ->and(config('mail.default'))->toBe('array')
        ->and(config('queue.default'))->toBe('sync');

    Storage::disk('local')->put('test-bootstrap.txt', 'isolated');

    expect(Storage::disk('local')->get('test-bootstrap.txt'))->toBe('isolated');
});

test('the default suite loads root and module migrations into sqlite memory', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite');

    foreach (['users', 'projects', 'project_members', 'tasks', 'task_comments', 'task_attachments'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Expected {$table} to be migrated for the default suite.");
    }
});
