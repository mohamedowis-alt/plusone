<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'add') {
        $email = strtolower(post('email', 190));
        $name = post('name', 120);
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || !mail_valid($email)) {
            flash('Enter a name and a valid email address.', 'bad');
        } elseif (strlen($password) < 10) {
            flash('The password needs at least 10 characters.', 'bad');
        } elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
            flash('Someone with that email is already on the team.', 'bad');
        } else {
            insert('users', ['email' => $email, 'name' => $name, 'pass_hash' => password_hash($password, PASSWORD_DEFAULT), 'created_at' => now()]);
            flash($name . ' can now sign in. Send them the password yourself, in person or by phone.');
        }
    } elseif ($action === 'remove') {
        $id = (int) post('id');
        if ($id === (int) $user['id']) {
            flash('You cannot remove yourself.', 'bad');
        } else {
            q('DELETE FROM users WHERE id = ?', [$id]);
            flash('Removed.');
        }
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $hash = (string) val('SELECT pass_hash FROM users WHERE id = ?', [$user['id']]);
        if (!password_verify($current, $hash)) {
            flash('Your current password is not right.', 'bad');
        } elseif (strlen($new) < 10) {
            flash('The new password needs at least 10 characters.', 'bad');
        } else {
            q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('Password changed.');
        }
    }
    redirect('team.php');
}

$users = rows('SELECT id, name, email, created_at FROM users ORDER BY id');

admin_head('Team', 'team');
?>
<div class="page-head">
  <div>
    <h1>Team</h1>
    <p>Everyone here can see requests, edit menus and change settings.</p>
  </div>
</div>

<div class="table-wrap">
<table class="table">
  <thead><tr><th>Name</th><th>Email</th><th>Added</th><th></th></tr></thead>
  <tbody>
<?php foreach ($users as $u): ?>
    <tr>
      <td><strong><?= e($u['name']) ?></strong><?= (int) $u['id'] === (int) $user['id'] ? ' <span class="muted">(you)</span>' : '' ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e(fmt_date($u['created_at'])) ?></td>
      <td><?php if ((int) $u['id'] !== (int) $user['id']): ?><form method="post" onsubmit="return confirm('Remove this person?');"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="linkbtn danger" type="submit">Remove</button></form><?php endif; ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>

<form class="card" method="post" style="margin-top:18px" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="add">
  <h2>Add someone</h2>
  <div class="grid3">
    <label>Name <input name="name" maxlength="120" required></label>
    <label>Email <input type="email" name="email" maxlength="190" required></label>
    <label>Password <span class="hint">At least 10 characters.</span><input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
  </div>
  <div class="actions"><button class="btn" type="submit">Add to the team</button></div>
</form>

<form class="card" method="post" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <h2>Change your password</h2>
  <div class="grid3">
    <label>Current password <input type="password" name="current" autocomplete="current-password" required></label>
    <label>New password <span class="hint">At least 10 characters.</span><input type="password" name="new" minlength="10" autocomplete="new-password" required></label>
  </div>
  <div class="actions"><button class="btn ghost" type="submit">Change password</button></div>
</form>
<?php admin_foot();
