<?php
declare(strict_types=1);

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

/** A trimmed, length-limited POST value. Arrays and control characters are dropped. */
function post(string $key, int $max = 500): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

/** The values of a POSTed checkbox group that are in the allowed list. */
function post_list(string $key, array $allowed): array
{
    $v = $_POST[$key] ?? [];
    if (!is_array($v)) {
        return [];
    }
    $out = [];
    foreach ($v as $item) {
        if (is_string($item) && in_array($item, $allowed, true) && !in_array($item, $out, true)) {
            $out[] = $item;
        }
    }
    return $out;
}

/** 01116417723 -> 201116417723, for wa.me links. */
function wa_number(string $raw): string
{
    $d = preg_replace('/\D+/', '', $raw) ?? '';
    if (str_starts_with($d, '00')) {
        $d = substr($d, 2);
    }
    if (strlen($d) === 11 && str_starts_with($d, '01')) {
        $d = '2' . $d;
    }
    return $d;
}

/** 201116417723 -> 0111 641 7723, for display. */
function phone_display(string $raw): string
{
    $d = wa_number($raw);
    if (strlen($d) === 12 && str_starts_with($d, '20')) {
        $d = '0' . substr($d, 2);
        return substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7);
    }
    return $raw;
}

function fmt_date(?string $ymd): string
{
    if (!$ymd) {
        return '';
    }
    $t = strtotime($ymd);
    return $t ? date('j M Y', $t) : (string) $ymd;
}

function fmt_datetime(?string $s): string
{
    if (!$s) {
        return '';
    }
    $t = strtotime($s);
    return $t ? date('j M Y, H:i', $t) : (string) $s;
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : 'section';
}

function request_ref(int $id): string
{
    return 'P1-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

/** The public address of the site root, with a trailing slash. */
function site_url(): string
{
    $saved = installed() ? trim((string) setting('site_url', '')) : '';
    if ($saved !== '') {
        return rtrim($saved, '/') . '/';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (basename($dir) === 'admin') {
        $dir = dirname($dir);
    }
    $dir = rtrim($dir, '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
}

/** An asset address that changes whenever the file changes, so updates show at once. */
function asset(string $path, string $prefix = ''): string
{
    $file = APP_ROOT . '/' . $path;
    return $prefix . $path . '?v=' . (is_file($file) ? filemtime($file) : '1');
}

/** The version line written at each update from GitHub, or '' when there is none. */
function site_version(): string
{
    $file = APP_ROOT . '/version.txt';
    return is_file($file) ? trim(mb_substr((string) file_get_contents($file), 0, 80)) : '';
}

// ---------------------------------------------------------------- drawing

/** One of the +1 plus marks as inline SVG, drawn in the current text colour. */
function mark(string $name, string $class = '', ?string $viewBox = null): string
{
    static $n = 0;
    $n++;
    $name = isset(MARKS[$name]) ? $name : 'plain';
    [$vb, $body] = MARKS[$name];
    $vb = $viewBox ?? $vb;
    // Clip-path ids must be unique on the page, so each drawing gets its own.
    $body = preg_replace_callback('/id="([^"]+)"/', fn ($m) => 'id="' . $m[1] . '-' . $n . '"', $body);
    $body = preg_replace_callback('/url\(#([^)]+)\)/', fn ($m) => 'url(#' . $m[1] . '-' . $n . ')', $body);
    return '<svg class="mark mark-' . $name . ($class ? ' ' . e($class) : '') . '" viewBox="' . $vb
        . '" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** The small drawn plus used inside sums. Display type never uses a typed plus. */
function plus(string $class = 'plus'): string
{
    return '<svg class="' . e($class) . '" viewBox="0 0 100 100" aria-hidden="true" focusable="false"><path d="'
        . PLUS_D . '"/></svg>';
}

/** "Good people + good food" with a drawn plus. */
function sum(string $a, string $b): string
{
    return '<span class="sum"><span>' . e($a) . '</span> ' . plus('plus sum-plus')
        . '<span class="vh">' . te(' plus ') . '</span> <span>' . e($b) . '</span></span>';
}

function logo(string $kind = 'inline', string $class = ''): string
{
    [$vb, $body] = LOGOS[$kind] ?? LOGOS['inline'];
    return '<svg class="logo logo-' . e($kind) . ($class ? ' ' . e($class) : '') . '" viewBox="' . $vb
        . '" role="img" aria-label="+1 by RDNA">' . $body . '</svg>';
}

// ---------------------------------------------------------------- requests

/** Label and value pairs describing a quote request, for the admin and the emails. */
function request_rows(array $r): array
{
    $style = STYLES[$r['style']][0] ?? '';
    $live = LIVE_COOKING[$r['live_cooking']] ?? '';
    $date = $r['event_date'] ? fmt_date($r['event_date']) : 'Not decided yet';
    $rows = [
        'Occasion'      => trim((SETTINGS_LABELS[$r['setting']] ?? '') . ': ' . $r['event_type'], ': '),
        'Guests'        => (string) $r['guests'],
        'Party'         => $r['parties'] !== '' ? $r['parties'] : 'Open to suggestions',
        'How we serve'  => $style,
        'Date'          => trim($date . ($r['time_of_day'] !== '' ? ', ' . strtolower($r['time_of_day']) : '')),
        'Where'         => trim($r['area'] . ($r['venue'] !== '' ? ' (' . strtolower($r['venue']) . ')' : '')),
        'Vibe'          => $r['vibe'],
        'Live cooking'  => $live,
        'Dietary needs' => $r['dietary'],
        'Budget'        => $r['budget'],
        'Notes'         => $r['notes'],
        'Name'          => $r['name'],
        'Company'       => $r['company'],
        'Mobile'        => $r['phone'],
        'Email'         => $r['email'],
        'Reply by'      => $r['contact_pref'],
        'Language'      => ($r['lang'] ?? 'en') === 'ar' ? 'Arabic' : '',
    ];
    return array_filter($rows, fn ($v) => trim((string) $v) !== '');
}

/** "Barbecue for 40 guests, 24 Oct 2026" */
function request_headline(array $r): string
{
    $what = $r['parties'] !== '' ? $r['parties'] : $r['event_type'];
    $s = $what . ' for ' . $r['guests'] . ' guests';
    if ($r['event_date']) {
        $s .= ', ' . fmt_date($r['event_date']);
    }
    return $s;
}
