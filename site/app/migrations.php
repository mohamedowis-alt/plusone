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

const SCHEMA_VERSION = 9;

function migration_steps(): array
{
    return [
        // 2: the first real set of menus replaces the sample menus the site started with.
        // Only menus still marked as untouched samples are removed. A party where the team
        // has already saved a menu of its own keeps it, and gets the new one hidden.
        2 => function (PDO $db, string $driver): void {
            $menus = require __DIR__ . '/menus.first.php';
            foreach (rows('SELECT id FROM menus WHERE is_sample = 1') as $old) {
                q('DELETE FROM dishes WHERE menu_id = ?', [$old['id']]);
                q('DELETE FROM menus WHERE id = ?', [$old['id']]);
            }
            foreach ($menus as $slug => $m) {
                $sid = val('SELECT id FROM sections WHERE slug = ?', [$slug]);
                if (!$sid) {
                    continue;
                }
                $teamHasOne = (int) val('SELECT COUNT(*) FROM menus WHERE section_id = ?', [$sid]) > 0;
                $mid = insert('menus', [
                    'section_id' => (int) $sid, 'title' => $m['title'], 'season' => $m['season'], 'intro' => $m['intro'],
                    'image' => '', 'pdf' => '', 'is_published' => $teamHasOne ? 0 : 1, 'is_sample' => 1,
                    'sort_order' => $teamHasOne ? 2 : 1, 'updated_at' => now(),
                ]);
                foreach ($m['dishes'] as $n => $d) {
                    insert('dishes', [
                        'menu_id' => $mid, 'course' => $d[0], 'name' => $d[1], 'description' => $d[2], 'tag' => $d[3],
                        'sort_order' => $n + 1,
                    ]);
                }
            }
        },
        // 3: the pizza party and coffee break menus were rewritten (more Italian, more international).
        // Only a menu still marked "To review" is rewritten. One the team has saved is left alone.
        3 => function (PDO $db, string $driver): void {
            $menus = require __DIR__ . '/menus.first.php';
            foreach (['pizza-party', 'coffee-break'] as $slug) {
                $sid = val('SELECT id FROM sections WHERE slug = ?', [$slug]);
                $drafts = $sid ? rows('SELECT id FROM menus WHERE section_id = ? AND is_sample = 1 ORDER BY id', [$sid]) : [];
                if (count($drafts) !== 1) {
                    continue;
                }
                $mid = (int) $drafts[0]['id'];
                $m = $menus[$slug];
                update('menus', ['title' => $m['title'], 'season' => $m['season'], 'intro' => $m['intro'], 'updated_at' => now()], $mid);
                q('DELETE FROM dishes WHERE menu_id = ?', [$mid]);
                foreach ($m['dishes'] as $n => $d) {
                    insert('dishes', [
                        'menu_id' => $mid, 'course' => $d[0], 'name' => $d[1], 'description' => $d[2], 'tag' => $d[3],
                        'sort_order' => $n + 1,
                    ]);
                }
            }
        },
        // 4: a second, American barbecue menu, and a new party: the taco bar.
        // Nothing existing is changed. Each addition is skipped if it is already there.
        4 => function (PDO $db, string $driver): void {
            $menus = require __DIR__ . '/menus.first.php';
            if (!val('SELECT id FROM sections WHERE slug = ?', ['taco-bar'])) {
                insert('sections', [
                    'slug' => 'taco-bar', 'name' => 'Taco bar', 'sum_a' => 'Tacos', 'sum_b' => 'your way',
                    'best_for' => 'Casual evenings, office Thursdays', 'mark' => 'plain', 'colour' => 'green',
                    'sort_order' => (int) val('SELECT MAX(sort_order) FROM sections') + 1, 'is_visible' => 1,
                ]);
            }
            foreach (['barbecue-backyard', 'taco-bar'] as $key) {
                $m = $menus[$key];
                $sid = val('SELECT id FROM sections WHERE slug = ?', [$m['section'] ?? $key]);
                if (!$sid || val('SELECT COUNT(*) FROM menus WHERE section_id = ? AND title = ?', [$sid, $m['title']])) {
                    continue;
                }
                $mid = insert('menus', [
                    'section_id' => (int) $sid, 'title' => $m['title'], 'season' => $m['season'], 'intro' => $m['intro'],
                    'image' => '', 'pdf' => '', 'is_published' => 1, 'is_sample' => 1,
                    'sort_order' => (int) val('SELECT COUNT(*) FROM menus WHERE section_id = ?', [$sid]) + 1,
                    'updated_at' => now(),
                ]);
                foreach ($m['dishes'] as $n => $d) {
                    insert('dishes', [
                        'menu_id' => $mid, 'course' => $d[0], 'name' => $d[1], 'description' => $d[2], 'tag' => $d[3],
                        'sort_order' => $n + 1,
                    ]);
                }
            }
        },
        // 5: a new party for October: Halloween, with its menu.
        // Nothing existing is changed. Each addition is skipped if it is already there.
        5 => function (PDO $db, string $driver): void {
            $menus = require __DIR__ . '/menus.first.php';
            if (!val('SELECT id FROM sections WHERE slug = ?', ['halloween'])) {
                insert('sections', [
                    'slug' => 'halloween', 'name' => 'Halloween', 'sum_a' => 'Trick', 'sum_b' => 'treat',
                    'best_for' => "Children's parties, schools, office Thursdays", 'mark' => 'plain', 'colour' => 'black',
                    'sort_order' => (int) val('SELECT MAX(sort_order) FROM sections') + 1, 'is_visible' => 1,
                ]);
            }
            $m = $menus['halloween'];
            $sid = val('SELECT id FROM sections WHERE slug = ?', ['halloween']);
            if ($sid && !val('SELECT COUNT(*) FROM menus WHERE section_id = ? AND title = ?', [$sid, $m['title']])) {
                $mid = insert('menus', [
                    'section_id' => (int) $sid, 'title' => $m['title'], 'season' => $m['season'], 'intro' => $m['intro'],
                    'image' => '', 'pdf' => '', 'is_published' => 1, 'is_sample' => 1,
                    'sort_order' => (int) val('SELECT COUNT(*) FROM menus WHERE section_id = ?', [$sid]) + 1,
                    'updated_at' => now(),
                ]);
                foreach ($m['dishes'] as $n => $d) {
                    insert('dishes', [
                        'menu_id' => $mid, 'course' => $d[0], 'name' => $d[1], 'description' => $d[2], 'tag' => $d[3],
                        'sort_order' => $n + 1,
                    ]);
                }
            }
        },
        // 6: Halloween gets its own mark, the pumpkin plus.
        // Only if the party still wears the plain plus it arrived with. A mark the team chose is left alone.
        6 => function (PDO $db, string $driver): void {
            q("UPDATE sections SET mark = 'pumpkin' WHERE slug = 'halloween' AND mark = 'plain'");
        },
        // 7: the WhatsApp number changed on 7 October 2026.
        // Only if the site still holds the number it started with. A number the team has set in Settings is left alone.
        7 => function (PDO $db, string $driver): void {
            q("UPDATE settings SET v = ? WHERE k = 'whatsapp' AND v = ?", ['201116417723', '201016649967']);
        },
        // 8: the Arabic page. Parties, menus and dishes each get an Arabic field beside the English one,
        // and a request remembers the language it was written in.
        // The Arabic is filled in only where the English is still the text the site started with.
        // Anything the team has rewritten is left alone and shows in English until its Arabic is typed in the admin.
        8 => function (PDO $db, string $driver): void {
            arabic_columns($db, $driver);
            arabic_fill($db);
        },
        // 9: the Arabic changes from formal Arabic to Egyptian Arabic, the way people speak.
        // Only an Arabic text that is still the formal one the site filled in itself is replaced.
        // Anything the team has typed or changed in the admin is left alone.
        9 => function (PDO $db, string $driver): void {
            arabic_fill($db, require __DIR__ . '/menus.ar.first.php');
        },
        // 10 => function (PDO $db, string $driver): void {
        //     $db->exec("ALTER TABLE requests ADD COLUMN source VARCHAR(80) NOT NULL DEFAULT ''");
        // },
    ];
}

function migrate(): void
{
    if ((int) setting('schema_version', '1') >= SCHEMA_VERSION) {
        return;
    }
    // One request at a time, so two visitors arriving together cannot both apply a step.
    $lock = @fopen(APP_ROOT . '/storage/migrate.lock', 'c');
    if ($lock) {
        flock($lock, LOCK_EX);
    }
    try {
        setting('__reset__');
        $current = (int) setting('schema_version', '1');
        $steps = migration_steps();
        $driver = (string) config()['db']['driver'];
        for ($v = $current + 1; $v <= SCHEMA_VERSION; $v++) {
            if (isset($steps[$v])) {
                $db = db();
                $db->beginTransaction();
                try {
                    $steps[$v]($db, $driver);
                    if ($db->inTransaction()) {
                        $db->commit();
                    }
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    // Leave the site running on what it has. The step is tried again on the next page load.
                    error_log('+1 update step ' . $v . ' failed: ' . $e->getMessage());
                    return;
                }
            }
            set_setting('schema_version', (string) $v);
        }
    } finally {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
