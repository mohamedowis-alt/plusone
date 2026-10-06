<?php
// Locked out? Run this from the server's command line, never from a browser:
//   php app/reset-password.php you@yourdomain.com "a new password"
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/bootstrap.php';
[$script, $email, $password] = $argv + [null, '', ''];
if (!installed() || $email === '' || strlen($password) < 10) {
    exit("Usage: php app/reset-password.php email \"new password of 10+ characters\"\n");
}
$n = q('UPDATE users SET pass_hash = ? WHERE email = ?', [password_hash($password, PASSWORD_DEFAULT), strtolower($email)])->rowCount();
echo $n ? "Password changed for $email\n" : "Nobody on the team has the email $email\n";
