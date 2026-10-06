<?php
declare(strict_types=1);

// Database changes that arrive with an update.
//
// The live database is never touched by a deploy, so when an update needs a new
// column or table it is described here, once, and the site applies it by itself
// the first time a page loads after the update.
//
// To add one:
//   1. Raise SCHEMA_VERSION by one.
//   2. Add a step under that number in migration_steps().
//   3. Make the same change in app/schema.php so new installs start with it.
// Steps must work on both MySQL and SQLite. Never edit a step that has already
// been deployed. Add a new one instead.

const SCHEMA_VERSION = 1;

function migration_steps(): array
{
    return [
        // 2 => function (PDO $db, string $driver): void {
        //     $db->exec("ALTER TABLE requests ADD COLUMN source VARCHAR(80) NOT NULL DEFAULT ''");
        // },
    ];
}

function migrate(): void
{
    $current = (int) setting('schema_version', '1');
    if ($current >= SCHEMA_VERSION) {
        return;
    }
    $steps = migration_steps();
    $driver = (string) config()['db']['driver'];
    for ($v = $current + 1; $v <= SCHEMA_VERSION; $v++) {
        if (isset($steps[$v])) {
            $steps[$v](db(), $driver);
        }
        set_setting('schema_version', (string) $v);
    }
}
