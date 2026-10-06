<?php
declare(strict_types=1);

// Sign-in for the admin area.

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('plusone_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    start_session();
    $id = (int) ($_SESSION['uid'] ?? 0);
    if (!$id) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $user = row('SELECT id, email, name FROM users WHERE id = ?', [$id]);
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('login.php');
    }
    header('Cache-Control: no-store');
    header('X-Frame-Options: DENY');
    return $u;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    $sent = (string) ($_POST['csrf'] ?? '');
    if ($sent === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(400);
        exit('This form has expired. Go back, reload the page and try again.');
    }
}

/** Too many wrong passwords from one address in 15 minutes? */
function login_blocked(): bool
{
    $since = date('Y-m-d H:i:s', time() - 900);
    return (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [client_ip(), $since]) >= 6;
}

function login_failed(): void
{
    insert('login_attempts', ['ip' => client_ip(), 'attempted_at' => now()]);
    q('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
}

function attempt_login(string $email, string $password): bool
{
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
    if (!$u || !password_verify($password, $u['pass_hash'])) {
        login_failed();
        return false;
    }
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return true;
}

function flash(?string $message = null, string $kind = 'ok'): ?array
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'kind' => $kind];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---------------------------------------------------------------- public form checks

/** A signed time stamp placed in the quote form. Stops the simplest robots. */
function form_stamp(): string
{
    $t = (string) time();
    return $t . '.' . hash_hmac('sha256', $t, (string) config()['secret']);
}

function form_stamp_ok(string $stamp): bool
{
    $parts = explode('.', $stamp, 2);
    if (count($parts) !== 2 || !ctype_digit($parts[0])) {
        return false;
    }
    if (!hash_equals(hash_hmac('sha256', $parts[0], (string) config()['secret']), $parts[1])) {
        return false;
    }
    $age = time() - (int) $parts[0];
    return $age >= 3 && $age <= 7 * 86400;
}
