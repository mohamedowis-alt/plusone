<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
if (!installed()) {
    redirect('../install.php');
}
if (current_user()) {
    redirect('index.php');
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(post('email', 190));
    if (login_blocked()) {
        $error = 'Too many tries. Wait 15 minutes and try again.';
    } elseif (attempt_login($email, (string) ($_POST['password'] ?? ''))) {
        redirect('index.php');
    } else {
        $error = 'That email and password do not match.';
    }
}
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sign in | +1 admin</title>
<link rel="icon" href="../assets/img/plus-one-symbol-colour.svg" type="image/svg+xml">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700;800&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css', '../')) ?>">
</head>
<body class="auth">
<main class="auth-card">
  <div class="auth-logo"><?= logo('inline') ?></div>
  <h1>Team sign-in</h1>
<?php if ($error !== ''): ?>
  <p class="note bad" role="alert"><?= e($error) ?></p>
<?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></label>
    <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit">Sign in</button>
  </form>
</main>
</body>
</html>
