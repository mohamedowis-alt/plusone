<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require_login();

$status = (string) ($_GET['status'] ?? '');
$status = isset(STATUSES[$status]) ? $status : '';

$where = $status !== '' ? 'WHERE status = ?' : '';
$params = $status !== '' ? [$status] : [];
$list = rows("SELECT * FROM requests $where ORDER BY id DESC LIMIT 300", $params);
$counts = [];
foreach (rows('SELECT status, COUNT(*) AS n FROM requests GROUP BY status') as $c) {
    $counts[$c['status']] = (int) $c['n'];
}
$notify = mail_list((string) setting('notify_email', ''));

admin_head('Requests', 'requests');
?>
<div class="page-head">
  <div>
    <h1>Requests</h1>
    <p>Every quote request sent from the site. New ones are also emailed to <?= $notify ? '<strong>' . e(implode(', ', $notify)) . '</strong>' : 'nobody yet' ?>. <a href="settings.php">Change</a></p>
  </div>
  <a class="btn ghost small" href="export.php">Download as a spreadsheet</a>
</div>

<div class="filters">
  <a href="index.php" class="<?= $status === '' ? 'on' : '' ?>">All (<?= array_sum($counts) ?>)</a>
<?php foreach (STATUSES as $key => $label): ?>
  <a href="index.php?status=<?= e($key) ?>" class="<?= $status === $key ? 'on' : '' ?>"><?= e($label) ?> (<?= $counts[$key] ?? 0 ?>)</a>
<?php endforeach; ?>
</div>

<?php if (!$list): ?>
<p class="card">Nothing here yet. When a guest sends a request from the site it shows up here and in your inbox.</p>
<?php else: ?>
<div class="table-wrap">
<table class="table">
  <thead><tr><th>Request</th><th>From</th><th>What</th><th>Event date</th><th>Status</th></tr></thead>
  <tbody>
<?php foreach ($list as $r): ?>
    <tr class="<?= $r['status'] === 'new' ? 'is-new' : '' ?>">
      <td><a class="strong" href="request.php?id=<?= (int) $r['id'] ?>"><?= e(request_ref((int) $r['id'])) ?></a><br><span class="muted"><?= e(fmt_datetime($r['created_at'])) ?></span></td>
      <td><?= e($r['name']) ?><?= $r['company'] !== '' ? ', ' . e($r['company']) : '' ?><br><span class="muted"><?= e($r['phone']) ?></span></td>
      <td><?= e(($r['parties'] !== '' ? $r['parties'] : $r['event_type'])) ?><br><span class="muted"><?= (int) $r['guests'] ?> guests, <?= e(strtolower(STYLES[$r['style']][0] ?? '')) ?></span></td>
      <td><?= $r['event_date'] !== '' ? e(fmt_date($r['event_date'])) : '<span class="muted">Open</span>' ?></td>
      <td><span class="pill s-<?= e($r['status']) ?>"><?= e(STATUSES[$r['status']] ?? $r['status']) ?></span><?php if (!(int) $r['email_sent']): ?><br><span class="muted" title="The notification email could not be sent. Check Settings.">Email not sent</span><?php endif; ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php admin_foot();
