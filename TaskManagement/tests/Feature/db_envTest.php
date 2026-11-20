<?php

use Illuminate\Support\Facades\DB;

it('connects to the dedicated PostgreSQL test database', function () {
    expect(DB::connection()->getPdo())->not->toBeNull();

    expect(DB::connection()->getDatabaseName())
        ->toBe('task_management_Test');
});
