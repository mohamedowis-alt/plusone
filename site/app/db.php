<?php
declare(strict_types=1);

/** Open a database from a config array. Used by the site and by the installer. */
function db_connect(array $c): PDO
{
    if (($c['driver'] ?? '') === 'sqlite') {
        $pdo = new PDO('sqlite:' . APP_ROOT . '/storage/' . basename((string) $c['file']));
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = 'mysql:host=' . $c['host'] . ';port=' . (int) ($c['port'] ?? 3306)
            . ';dbname=' . $c['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string) $c['user'], (string) $c['pass']);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = db_connect(config()['db']);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function val(string $sql, array $params = [])
{
    $r = q($sql, $params)->fetch(PDO::FETCH_NUM);
    return $r === false ? null : $r[0];
}

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
        . implode(', ', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, int $id): void
{
    $set = implode(', ', array_map(fn ($c) => $c . ' = ?', array_keys($data)));
    q('UPDATE ' . $table . ' SET ' . $set . ' WHERE id = ?', [...array_values($data), $id]);
}

// ---------------------------------------------------------------- settings

function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null || $key === '__reset__') {
        $cache = [];
        foreach (rows('SELECT k, v FROM settings') as $r) {
            $cache[$r['k']] = $r['v'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    if (val('SELECT COUNT(*) FROM settings WHERE k = ?', [$key])) {
        q('UPDATE settings SET v = ? WHERE k = ?', [$value, $key]);
    } else {
        q('INSERT INTO settings (k, v) VALUES (?, ?)', [$key, $value]);
    }
    setting('__reset__');
}

// ---------------------------------------------------------------- menus

/** Sections with their published menus and dishes, for the public page. */
function public_sections(): array
{
    $sections = rows('SELECT * FROM sections WHERE is_visible = 1 ORDER BY sort_order, id');
    $menus = rows('SELECT * FROM menus WHERE is_published = 1 ORDER BY sort_order, id');
    $dishes = rows('SELECT d.* FROM dishes d JOIN menus m ON m.id = d.menu_id WHERE m.is_published = 1 ORDER BY d.sort_order, d.id');
    $byMenu = [];
    foreach ($dishes as $d) {
        $byMenu[$d['menu_id']][] = $d;
    }
    $bySection = [];
    foreach ($menus as $m) {
        $m['dishes'] = $byMenu[$m['id']] ?? [];
        $bySection[$m['section_id']][] = $m;
    }
    foreach ($sections as &$s) {
        $s['menus'] = $bySection[$s['id']] ?? [];
    }
    return $sections;
}
