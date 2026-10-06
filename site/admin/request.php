<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$r = row('SELECT * FROM requests WHERE id = ?', [$id]);
if (!$r) {
    flash('That request no longer exists.', 'bad');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'save') {
        $status = post('status');
        update('requests', [
            'status' => isset(STATUSES[$status]) ? $status : $r['status'],
            'admin_note' => post('admin_note', 4000),
        ], $id);
        flash('Saved.');
    } elseif ($action === 'resend') {
        $sent = mail_request_to_team($r);
        if ($sent['ok']) {
            q('UPDATE requests SET email_sent = 1 WHERE id = ?', [$id]);
            flash('Email sent to the team.');
        } else {
            flash('The email could not be sent: ' . $sent['error'], 'bad');
        }
    } elseif ($action === 'delete') {
        q('DELETE FROM requests WHERE id = ?', [$id]);
        flash('Request deleted.');
        redirect('index.php');
    }
    redirect('request.php?id=' . $id);
}

$wa = 'https://wa.me/' . wa_number($r['phone']) . '?text=' . rawurlencode('Hello ' . strtok($r['name'], ' ') . ', this is +1 by RDNA about your request (' . request_ref($id) . ').');

admin_head(request_ref($id), 'requests');
?>
<div class="page-head">
  <div>
    <p><a href="index.php">All requests</a></p>
    <h1><?= e(request_headline($r)) ?></h1>
    <p><?= e(request_ref($id)) ?>, sent <?= e(fmt_datetime($r['created_at'])) ?></p>
  </div>
  <div class="row">
    <a class="btn" href="<?= e($wa) ?>" target="_blank" rel="noopener">Reply on WhatsApp</a>
    <?php if ($r['email'] !== ''): ?><a class="btn ghost" href="mailto:<?= e($r['email']) ?>?subject=<?= rawurlencode('Your +1 request ' . request_ref($id)) ?>">Email</a><?php endif; ?>
    <a class="btn ghost" href="tel:<?= e(preg_replace('/[^\d+]/', '', $r['phone'])) ?>">Call</a>
  </div>
</div>

<div class="detail">
  <div class="card">
    <h2>The request</h2>
    <dl class="facts">
<?php foreach (request_rows($r) as $label => $value): ?>
      <dt><?= e($label) ?></dt><dd><?= e($value) ?></dd>
<?php endforeach; ?>
    </dl>
  </div>
  <div>
    <form class="card" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <h2>Where it stands</h2>
      <label>Status
        <select name="status">
<?php foreach (STATUSES as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $r['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
      </label>
      <label style="margin-top:16px">Team notes <span class="hint">Only the team sees this.</span>
        <textarea name="admin_note" rows="5"><?= e($r['admin_note']) ?></textarea>
      </label>
      <div class="actions"><button class="btn" type="submit">Save</button></div>
    </form>
    <div class="card">
      <h2>Email</h2>
      <p class="muted"><?= (int) $r['email_sent'] ? 'The team was emailed about this request.' : 'The notification email was not sent. Check the email settings, then send it again.' ?></p>
      <form method="post" class="actions">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="resend">
        <button class="btn ghost small" type="submit">Send the email again</button>
      </form>
    </div>
    <form class="card" method="post" onsubmit="return confirm('Delete this request for good?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <button class="linkbtn danger" type="submit">Delete this request</button>
    </form>
  </div>
</div>
<?php admin_foot();
