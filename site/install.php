<?php
declare(strict_types=1);

// One-time set-up. Open this page once and fill it in.
// After that it answers "not found", so it is safe for updates to upload it again.

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/schema.php';

const INSTALL_LOCK = APP_ROOT . '/storage/installed.lock';

if (installed()) {
    http_response_code(404);
    exit('Not found');
}

$errors = [];
$done = false;
$manualConfig = '';
$locked = is_file(INSTALL_LOCK);

$f = [
    'driver'  => $_POST['driver'] ?? 'mysql',
    'host'    => post('host') ?: 'localhost',
    'port'    => post('port') ?: '3306',
    'dbname'  => post('dbname'),
    'dbuser'  => post('dbuser'),
    'dbpass'  => (string) ($_POST['dbpass'] ?? ''),
    'name'    => post('name'),
    'email'   => strtolower(post('email')),
    'notify'  => strtolower(post('notify')),
    'from'    => strtolower(post('from')),
];

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    if ($f['name'] === '') {
        $errors[] = 'Enter your name.';
    }
    if (!mail_valid($f['email'])) {
        $errors[] = 'Enter a valid email address to sign in with.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Choose a password of at least 10 characters.';
    }
    if (!mail_list($f['notify'])) {
        $errors[] = 'Enter the email address that should receive quote requests.';
    }
    if (!mail_valid($f['from'])) {
        $errors[] = 'Enter the address the site sends from, for example hello@ your domain.';
    }
    if (!is_writable(APP_ROOT . '/storage') || !is_writable(APP_ROOT . '/uploads/menus')) {
        $errors[] = 'The folders "storage" and "uploads/menus" must be writable by the web server.';
    }

    $db = $f['driver'] === 'sqlite'
        ? ['driver' => 'sqlite', 'file' => 'plusone-' . bin2hex(random_bytes(8)) . '.sqlite']
        : ['driver' => 'mysql', 'host' => $f['host'], 'port' => (int) $f['port'], 'name' => $f['dbname'],
            'user' => $f['dbuser'], 'pass' => $f['dbpass']];
    if ($db['driver'] === 'mysql' && ($f['dbname'] === '' || $f['dbuser'] === '')) {
        $errors[] = 'Enter the database name and user.';
    }

    if (!$errors) {
        try {
            $pdo = db_connect($db);
            create_schema($pdo, $db['driver']);
            if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
                if ((int) $pdo->query('SELECT COUNT(*) FROM sections')->fetchColumn() === 0) {
                    seed_content($pdo, implode(', ', mail_list($f['notify'])), $f['from']);
                }
                $st = $pdo->prepare('INSERT INTO users (email, name, pass_hash, created_at) VALUES (?, ?, ?, ?)');
                $st->execute([$f['email'], $f['name'], password_hash($password, PASSWORD_DEFAULT), now()]);
            }
            $config = "<?php\n// Created by install.php. Keep this file private.\nreturn "
                . var_export(['db' => $db, 'secret' => bin2hex(random_bytes(32))], true) . ";\n";
            if (@file_put_contents(APP_ROOT . '/config.php', $config) === false) {
                $manualConfig = $config;
            }
            @file_put_contents(INSTALL_LOCK, 'Installed ' . now() . "\n");
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'The database did not accept the connection: ' . $e->getMessage();
            if ($db['driver'] === 'sqlite') {
                @unlink(APP_ROOT . '/storage/' . $db['file']);
            }
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Set up the +1 website</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="auth">
<main class="auth-card wide">
  <div class="auth-logo"><?= logo('inline') ?></div>
<?php if ($locked): ?>
  <h1>Already set up</h1>
  <p>This site was set up before, but its settings file <strong>config.php</strong> is missing. Put config.php back from your backup. To start again from nothing instead, delete <strong>storage/installed.lock</strong> on the server and reload this page.</p>
<?php elseif ($done && $manualConfig !== ''): ?>
  <h1>One step left</h1>
  <p>The database is ready, but the server would not let this page save its settings file. Create a file named <strong>config.php</strong> next to index.php with exactly this inside, then open the site.</p>
  <textarea rows="14" readonly><?= e($manualConfig) ?></textarea>
<?php elseif ($done): ?>
  <h1>The site is set up</h1>
  <p><a class="btn" href="admin/login.php">Sign in to the admin</a> <a class="btn ghost" href="index.php">See the site</a></p>
<?php else: ?>
  <h1>Set up the +1 website</h1>
  <p>Fill this in once. It creates the database tables, the six parties with a sample menu each, and your sign-in.</p>
  <?php foreach ($errors as $err): ?><p class="note bad"><?= e($err) ?></p><?php endforeach; ?>
  <form method="post" autocomplete="off">
    <h2>1. Database</h2>
    <div class="choice">
      <label><input type="radio" name="driver" value="mysql" <?= $f['driver'] !== 'sqlite' ? 'checked' : '' ?>> MySQL or MariaDB <span class="hint">Create an empty database in your hosting panel first.</span></label>
      <label><input type="radio" name="driver" value="sqlite" <?= $f['driver'] === 'sqlite' ? 'checked' : '' ?>> Built-in file database <span class="hint">Nothing to create. Fine for a site of this size.</span></label>
    </div>
    <div class="grid2" id="mysql-fields">
      <label>Database name <input name="dbname" value="<?= e($f['dbname']) ?>"></label>
      <label>Database user <input name="dbuser" value="<?= e($f['dbuser']) ?>"></label>
      <label>Database password <input type="password" name="dbpass" value=""></label>
      <label>Server <input name="host" value="<?= e($f['host']) ?>"></label>
      <label>Port <input name="port" value="<?= e($f['port']) ?>" inputmode="numeric"></label>
    </div>
    <h2>2. Your sign-in</h2>
    <div class="grid2">
      <label>Your name <input name="name" value="<?= e($f['name']) ?>" required></label>
      <label>Your email <input type="email" name="email" value="<?= e($f['email']) ?>" required></label>
      <label>Password <span class="hint">At least 10 characters.</span> <input type="password" name="password" minlength="10" required></label>
    </div>
    <h2>3. Quote requests</h2>
    <div class="grid2">
      <label>Send requests to <span class="hint">One address, or several separated by commas.</span> <input name="notify" value="<?= e($f['notify']) ?>" required></label>
      <label>Send from <span class="hint">An address on your own domain.</span> <input type="email" name="from" value="<?= e($f['from']) ?>" required></label>
    </div>
    <p><button class="btn" type="submit">Set up the site</button></p>
  </form>
  <script>
  (function () {
    var box = document.getElementById('mysql-fields');
    function sync() { box.hidden = document.querySelector('input[name=driver]:checked').value === 'sqlite'; }
    document.querySelectorAll('input[name=driver]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
  })();
  </script>
<?php endif; ?>
</main>
</body>
</html>
