<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;

$roles = Role::query()->orderBy('guard_name')->orderBy('name')->get(['name', 'guard_name']);
foreach ($roles as $role) {
    echo $role->guard_name . ':' . $role->name . "\n";
}
