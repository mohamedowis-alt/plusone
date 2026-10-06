<?php
declare(strict_types=1);

// One-click updates from GitHub, started from Admin > Updates.
//
// An update replaces the site's code and design only. It never touches
// config.php, the database, storage/, the team's uploaded menu files or the
// root .htaccess (hosting panels keep their own settings in that file).
//
// Before anything is written, every PHP file in the update is checked for
// syntax errors and the files about to be replaced are saved as a backup, so a
// broken update is refused and a bad one can be rolled back.

const UPDATE_BACKUPS = APP_ROOT . '/storage/backups';

// Files an update may write, relative to the site folder.
const UPDATE_ALLOW = '#^('
    . '(index|quote|install)\.php|robots\.txt'
    . '|(admin|app|assets)/[A-Za-z0-9_./\-]+\.(php|css|js|json|svg|png|jpg|jpeg|webp|ico|woff2?|otf|ttf|txt)'
    . '|app/\.htaccess'
    . ')$#';

// An update missing any of these is not a complete copy of the site.
const UPDATE_MUST_HAVE = ['index.php', 'quote.php', 'app/bootstrap.php', 'app/update.php', 'admin/index.php', 'admin/updates.php', 'assets/css/site.css'];

function update_settings(): array
{
    return [
        'repo' => (string) setting('update_repo', ''),
        'branch' => (string) setting('update_branch', 'main') ?: 'main',
        'token' => (string) setting('update_token', ''),
    ];
}

/** What is live now: ['sha' => ..., 'date' => ..., 'message' => ..., 'updated' => ...] or []. */
function update_current(): array
{
    $v = json_decode((string) setting('update_version', ''), true);
    return is_array($v) ? $v : [];
}

function update_api(): string
{
    // Overridable in config.php for testing. Normally GitHub.
    return rtrim((string) (config()['github_api'] ?? 'https://api.github.com'), '/');
}

/** GET a GitHub address. Returns [status code, body, error text]. */
function update_http_get(string $url, string $token, bool $binary = false): array
{
    $headers = [
        'User-Agent: PlusOneByRdnaSite',
        'Accept: ' . ($binary ? '*/*' : 'application/vnd.github+json'),
        'X-GitHub-Api-Version: 2022-11-28',
    ];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => $binary ? 90 : 20, CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return [$code, $body === false ? '' : (string) $body, $err];
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => $binary ? 90 : 20,
        'follow_location' => 1, 'max_redirects' => 5, 'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $code = (int) $m[1];   // the last status line is the final answer after redirects
        }
    }
    return [$code, $body === false ? '' : (string) $body, $body === false ? 'no answer' : ''];
}

function update_github_error(int $code, string $err): string
{
    if ($code === 401) {
        return 'GitHub refused the access token. Create a new one and save it below.';
    }
    if ($code === 403 || $code === 429) {
        return 'GitHub is limiting requests from this server. Try again in an hour, or add an access token below.';
    }
    if ($code === 404) {
        return 'GitHub could not find that repository or branch. Check the name, and the access token if the repository is private.';
    }
    if ($code === 0) {
        return 'This server could not reach GitHub' . ($err !== '' ? ' (' . $err . ')' : '') . '. Try again in a minute.';
    }
    return 'GitHub answered with an error (' . $code . '). Try again in a minute.';
}

/**
 * Ask GitHub for the newest version.
 * ['ok' => true, 'latest' => [...], 'current' => [...], 'available' => bool, 'checks' => passed|failed|running|unknown]
 */
function update_check(): array
{
    $s = update_settings();
    if (!preg_match('#^[\w.-]+/[\w.-]+$#', $s['repo'])) {
        return ['ok' => false, 'error' => 'Set the repository first, as owner/name.'];
    }
    [$code, $body, $err] = update_http_get(update_api() . '/repos/' . $s['repo'] . '/commits/' . rawurlencode($s['branch']), $s['token']);
    if ($code !== 200) {
        return ['ok' => false, 'error' => update_github_error($code, $err)];
    }
    $j = json_decode($body, true);
    $sha = (string) ($j['sha'] ?? '');
    if (!preg_match('/^[0-9a-f]{40}$/', $sha)) {
        return ['ok' => false, 'error' => 'GitHub sent an answer this site did not understand. Try again in a minute.'];
    }
    $latest = [
        'sha' => $sha,
        'date' => (string) ($j['commit']['committer']['date'] ?? ''),
        'message' => mb_substr((string) strtok((string) ($j['commit']['message'] ?? ''), "\n"), 0, 200),
    ];
    $current = update_current();
    return [
        'ok' => true,
        'latest' => $latest,
        'current' => $current,
        'available' => ($current['sha'] ?? '') !== $sha,
        'checks' => update_checks_state($s, $sha),
    ];
}

/** Did GitHub's own check of this version pass? passed, failed, running or unknown. */
function update_checks_state(array $s, string $sha): string
{
    [$code, $body] = update_http_get(update_api() . '/repos/' . $s['repo'] . '/actions/runs?per_page=20&head_sha=' . $sha, $s['token']);
    if ($code !== 200) {
        return 'unknown';
    }
    $runs = json_decode($body, true)['workflow_runs'] ?? null;
    if (!is_array($runs) || !$runs) {
        return 'unknown';
    }
    $state = 'passed';
    foreach ($runs as $run) {
        if (($run['status'] ?? '') !== 'completed') {
            $state = $state === 'failed' ? 'failed' : 'running';
        } elseif (!in_array($run['conclusion'] ?? '', ['success', 'skipped', 'neutral'], true)) {
            $state = 'failed';
        }
    }
    return $state;
}

function update_safe_rel(string $rel): bool
{
    return $rel !== '' && $rel[0] !== '/' && !str_contains($rel, '..') && !str_contains($rel, '\\')
        && !str_contains($rel, "\0") && !str_contains($rel, '//');
}

function update_write_file(string $rel, string $data): bool
{
    $path = APP_ROOT . '/' . $rel;
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $tmp = $path . '.tmp' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $data) === false) {
        return false;
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($path, true);
    }
    return true;
}

/** Save the current copy of the files about to be replaced. Keeps the last five. */
function update_backup(array $rels): ?string
{
    if (!is_dir(UPDATE_BACKUPS) && !@mkdir(UPDATE_BACKUPS, 0750, true)) {
        return null;
    }
    $file = UPDATE_BACKUPS . '/code-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip';
    $z = new ZipArchive();
    if ($z->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return null;
    }
    foreach ($rels as $rel) {
        if (is_file(APP_ROOT . '/' . $rel)) {
            $z->addFile(APP_ROOT . '/' . $rel, $rel);
        }
    }
    $z->addFromString('.version.json', json_encode(update_current()));
    $z->close();
    $all = glob(UPDATE_BACKUPS . '/code-*.zip') ?: [];
    sort($all);
    while (count($all) > 5) {
        @unlink(array_shift($all));
    }
    return is_file($file) ? $file : null;
}

/** A PHP file with a syntax error would take the whole site down, so refuse it. */
function update_php_problem(string $rel, string $code): string
{
    if (!function_exists('token_get_all')) {
        return '';
    }
    try {
        token_get_all($code, TOKEN_PARSE);
    } catch (Throwable $e) {
        return $rel . ' (line ' . $e->getLine() . ': ' . $e->getMessage() . ')';
    }
    return '';
}

/** Download the newest version and install it. */
function update_run(): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'This hosting has no ZIP support for PHP. Ask the host to enable the "zip" extension.'];
    }
    $check = update_check();
    if (!$check['ok']) {
        return $check;
    }
    if ($check['checks'] === 'failed') {
        return ['ok' => false, 'error' => 'This version did not pass its checks on GitHub, so it was not installed. Nothing was changed.'];
    }
    if ($check['checks'] === 'running') {
        return ['ok' => false, 'error' => 'GitHub is still checking this version. Try again in a minute or two. Nothing was changed.'];
    }
    $s = update_settings();
    $latest = $check['latest'];
    @set_time_limit(180);

    [$code, $body, $err] = update_http_get(update_api() . '/repos/' . $s['repo'] . '/zipball/' . $latest['sha'], $s['token'], true);
    if ($code !== 200 || $body === '') {
        return ['ok' => false, 'error' => update_github_error($code, $err)];
    }
    if (!is_dir(UPDATE_BACKUPS)) {
        @mkdir(UPDATE_BACKUPS, 0750, true);
    }
    $tmp = UPDATE_BACKUPS . '/download-' . bin2hex(random_bytes(6)) . '.tmp';
    if (@file_put_contents($tmp, $body) === false) {
        return ['ok' => false, 'error' => 'The update could not be saved on this server. Check that the storage folder is writable.'];
    }
    unset($body);
    try {
        return update_apply($tmp, $latest);
    } finally {
        @unlink($tmp);
    }
}

function update_apply(string $zipFile, array $latest): array
{
    $z = new ZipArchive();
    if ($z->open($zipFile) !== true) {
        return ['ok' => false, 'error' => 'The download was damaged. Try again. Nothing was changed.'];
    }
    $files = [];
    for ($i = 0; $i < $z->numFiles; $i++) {
        $name = (string) $z->getNameIndex($i);
        $slash = strpos($name, '/');
        $inRepo = $slash === false ? '' : substr($name, $slash + 1);        // drop GitHub's "owner-repo-sha/"
        if (!str_starts_with($inRepo, 'site/') || str_ends_with($inRepo, '/')) {
            continue;                                                       // only the site folder goes on the server
        }
        $rel = substr($inRepo, 5);
        if (!update_safe_rel($rel) || !preg_match(UPDATE_ALLOW, $rel)) {
            continue;
        }
        $files[$rel] = (string) $z->getFromIndex($i);
    }
    $z->close();

    foreach (UPDATE_MUST_HAVE as $must) {
        if (!isset($files[$must]) || $files[$must] === '') {
            return ['ok' => false, 'error' => 'The update is incomplete (' . $must . ' is missing). Nothing was changed.'];
        }
    }
    foreach ($files as $rel => $data) {
        if (str_ends_with($rel, '.php') && ($problem = update_php_problem($rel, $data)) !== '') {
            return ['ok' => false, 'error' => 'The update has an error in ' . $problem . '. Nothing was changed.'];
        }
    }

    // Only write what actually differs. Quicker, and the backup stays small.
    $changed = [];
    foreach ($files as $rel => $data) {
        $path = APP_ROOT . '/' . $rel;
        if (!is_file($path) || hash('sha256', $data) !== hash_file('sha256', $path)) {
            $changed[$rel] = $data;
        }
    }
    if ($changed && update_backup(array_keys($changed)) === null) {
        return ['ok' => false, 'error' => 'A backup could not be made first, so the update was stopped. Check that the storage folder is writable. Nothing was changed.'];
    }
    $failed = [];
    foreach ($changed as $rel => $data) {
        if (!update_write_file($rel, $data)) {
            $failed[] = $rel;
        }
    }
    if ($failed) {
        return ['ok' => false, 'error' => 'Some files could not be written: ' . implode(', ', array_slice($failed, 0, 5))
            . '. Use "Roll back last update" and check the folder permissions.'];
    }

    $version = $latest + ['updated' => gmdate('c')];
    set_setting('update_version', (string) json_encode($version));
    update_stamp($version);
    return ['ok' => true, 'version' => $version, 'files' => count($changed)];
}

/** The short version line shown in the admin sidebar. */
function update_stamp(array $version): void
{
    $file = APP_ROOT . '/version.txt';
    if (empty($version['sha'])) {
        @unlink($file);
        return;
    }
    $when = !empty($version['updated']) ? date('j M Y', (int) strtotime((string) $version['updated'])) : '';
    @file_put_contents($file, substr((string) $version['sha'], 0, 7) . ($when !== '' ? ', ' . $when : '') . "\n");
}

/** When updates were made, newest first. */
function update_backups(): array
{
    $all = glob(UPDATE_BACKUPS . '/code-*.zip') ?: [];
    rsort($all);
    return $all;
}

/** Put back the files as they were before the last update. */
function update_rollback(): array
{
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'error' => 'This hosting has no ZIP support for PHP.'];
    }
    $all = update_backups();
    if (!$all) {
        return ['ok' => false, 'error' => 'There is no earlier version to go back to.'];
    }
    $file = $all[0];
    $z = new ZipArchive();
    if ($z->open($file) !== true) {
        return ['ok' => false, 'error' => 'The backup could not be opened.'];
    }
    $version = json_decode((string) $z->getFromName('.version.json'), true);
    $failed = [];
    for ($i = 0; $i < $z->numFiles; $i++) {
        $rel = (string) $z->getNameIndex($i);
        if (!update_safe_rel($rel) || !preg_match(UPDATE_ALLOW, $rel)) {
            continue;
        }
        if (!update_write_file($rel, (string) $z->getFromIndex($i))) {
            $failed[] = $rel;
        }
    }
    $z->close();
    if ($failed) {
        return ['ok' => false, 'error' => 'Some files could not be put back: ' . implode(', ', array_slice($failed, 0, 5)) . '.'];
    }
    @unlink($file);
    $version = is_array($version) ? $version : [];
    set_setting('update_version', (string) json_encode($version));
    update_stamp($version);
    return ['ok' => true, 'version' => $version];
}
