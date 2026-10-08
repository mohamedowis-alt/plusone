<?php
declare(strict_types=1);

// Two languages: English and Arabic.
//
// English is the text written in the pages. Arabic comes from two places:
//   app/lang.ar.php   the site's own words (headlines, buttons, the quote questions)
//   the database      the Arabic name of each party, menu and dish, typed in the admin
// The Arabic page is the same page at ?lang=ar. It reads right to left.
// Where an Arabic text is missing, the English one is shown in its place.

const LANGS = ['en' => 'English', 'ar' => 'العربية'];

// The fields that have an Arabic twin (name -> name_ar), with their length.
const ARABIC_FIELDS = [
    'sections' => ['name' => 80, 'sum_a' => 40, 'sum_b' => 40, 'best_for' => 160],
    'menus'    => ['title' => 160, 'season' => 80, 'intro' => 1000],
    'dishes'   => ['course' => 80, 'name' => 160, 'description' => 400],
];

/** The language of this page: 'en' or 'ar'. */
function lang(?string $set = null): string
{
    static $lang = null;
    if ($set !== null) {
        $lang = isset(LANGS[$set]) ? $set : 'en';
    }
    if ($lang === null) {
        $v = $_GET['lang'] ?? ($_POST['lang'] ?? '');
        $lang = $v === 'ar' ? 'ar' : 'en';
    }
    return $lang;
}

/** A text in the given language. Not escaped: some texts carry a line break or a link. */
function tr(string $s, string $lang): string
{
    static $ar = null;
    if ($lang !== 'ar') {
        return $s;
    }
    $ar ??= require __DIR__ . '/lang.ar.php';
    if (!isset($ar[$s])) {
        return $s;
    }
    // "+1" inside Arabic text needs a hidden left-to-right mark, or it shows as "1+".
    return str_replace('{+1}', "\u{200E}+1", $ar[$s]);
}

function t(string $s): string
{
    return tr($s, lang());
}

/** The same, made safe for HTML. */
function te(string $s): string
{
    return e(t($s));
}

/** A field of a party, menu or dish in the page's language. */
function loc(array $row, string $field): string
{
    if (lang() === 'ar' && trim((string) ($row[$field . '_ar'] ?? '')) !== '') {
        return (string) $row[$field . '_ar'];
    }
    return (string) ($row[$field] ?? '');
}

/** Phone numbers, references and @names keep their left-to-right order inside Arabic text. Escaped. */
function ltr(string $s): string
{
    return lang() === 'ar' ? '<bdi dir="ltr">' . e($s) . '</bdi>' : e($s);
}

/** True once the database has its Arabic fields (update step 8). */
function arabic_ready(): bool
{
    return installed() && (int) setting('schema_version', '1') >= 8;
}

function column_exists(PDO $db, string $driver, string $table, string $column): bool
{
    $sql = $driver === 'sqlite' ? 'PRAGMA table_info(' . $table . ')' : 'SHOW COLUMNS FROM ' . $table;
    $key = $driver === 'sqlite' ? 'name' : 'Field';
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $c) {
        if (strcasecmp((string) $c[$key], $column) === 0) {
            return true;
        }
    }
    return false;
}

/** Adds the Arabic fields to a database that does not have them yet. Safe to run twice. */
function arabic_columns(PDO $db, string $driver): void
{
    foreach (ARABIC_FIELDS as $table => $fields) {
        foreach ($fields as $field => $length) {
            if (!column_exists($db, $driver, $table, $field . '_ar')) {
                $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $field . '_ar VARCHAR(' . $length . ") NOT NULL DEFAULT ''");
            }
        }
    }
    if (!column_exists($db, $driver, 'requests', 'lang')) {
        $db->exec("ALTER TABLE requests ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT 'en'");
    }
}

/**
 * Fills in the Arabic for the parties, menus and dishes the site started with.
 * Only where the Arabic is still empty and the English is still the starting text,
 * so anything the team has written or changed is left alone.
 */
function arabic_fill(PDO $db): void
{
    $map = require __DIR__ . '/menus.ar.php';
    foreach (ARABIC_FIELDS as $table => $fields) {
        $cols = [];
        foreach ($fields as $field => $length) {
            $cols[] = $field;
            $cols[] = $field . '_ar';
        }
        $all = $db->query('SELECT id, ' . implode(', ', $cols) . ' FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all as $r) {
            $set = [];
            foreach ($fields as $field => $length) {
                $en = (string) $r[$field];
                if ((string) $r[$field . '_ar'] === '' && isset($map[$en]) && mb_strlen($map[$en]) <= $length) {
                    $set[$field . '_ar'] = $map[$en];
                }
            }
            if ($set) {
                $st = $db->prepare('UPDATE ' . $table . ' SET ' . implode(', ', array_map(fn ($c) => $c . ' = ?', array_keys($set))) . ' WHERE id = ?');
                $st->execute([...array_values($set), $r['id']]);
            }
        }
    }
}
