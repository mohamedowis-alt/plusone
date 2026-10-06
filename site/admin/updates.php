<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require dirname(__DIR__) . '/app/update.php';
require_login();

$fmt = function (array $v): string {
    if (empty($v['sha'])) {
        return 'not recorded yet';
    }
    $s = substr((string) $v['sha'], 0, 7);
    if (!empty($v['message'])) {
        $s .= ', "' . $v['message'] . '"';
    }
    if (!empty($v['date'])) {
        $s .= ' (' . date('j M Y', (int) strtotime((string) $v['date'])) . ')';
    }
    return $s;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'settings') {
        $repo = trim(post('update_repo', 140));
        // Accept a pasted GitHub address as well as owner/name.
        $repo = preg_replace('#^https?://github\.com/#i', '', $repo) ?? $repo;
        $repo = preg_replace('#(\.git)?/?$#', '', $repo) ?? $repo;
        $branch = post('update_branch', 100) ?: 'main';
        if (!preg_match('#^[\w.-]+/[\w.-]+$#', $repo)) {
            flash('The repository should look like owner/name, for example mohamedowis-alt/plus-one-website.', 'bad');
        } elseif (!preg_match('#^[\w./-]+$#', $branch)) {
            flash('That branch name does not look right. It is usually "main".', 'bad');
        } else {
            set_setting('update_repo', $repo);
            set_setting('update_branch', $branch);
            $token = trim((string) ($_POST['update_token'] ?? ''));
            if ($token !== '') {
                set_setting('update_token', mb_substr($token, 0, 300));
            } elseif (isset($_POST['forget_token'])) {
                set_setting('update_token', '');
            }
            $check = update_check();
            flash($check['ok'] ? 'Saved. This site can reach the repository.' : 'Saved, but: ' . $check['error'], $check['ok'] ? 'ok' : 'bad');
        }
    } elseif ($action === 'run') {
        $r = update_run();
        if ($r['ok']) {
            flash('Updated to ' . $fmt($r['version']) . '. ' . ($r['files'] === 0 ? 'The files were already the same.' : $r['files'] . ' files changed.')
                . ' Menus, requests and settings were not touched.');
        } else {
            flash($r['error'], 'bad');
        }
    } elseif ($action === 'rollback') {
        $r = update_rollback();
        flash($r['ok'] ? 'Rolled back. The site is as it was before the last update.' : $r['error'], $r['ok'] ? 'ok' : 'bad');
    }
    redirect('updates.php');
}

$s = update_settings();
$current = update_current();
$check = $s['repo'] !== '' ? update_check() : null;
$backups = update_backups();
$checksText = [
    'passed' => 'It passed its checks on GitHub.',
    'failed' => 'It did not pass its checks on GitHub, so it cannot be installed.',
    'running' => 'GitHub is still checking it. Reload this page in a minute or two.',
    'unknown' => 'GitHub has no check result for it. This site still checks every file itself before installing.',
];

admin_head('Updates', 'updates');
?>
<div class="page-head">
  <div>
    <h1>Updates</h1>
    <p>New versions of the site come from GitHub. An update changes the code and the design only. Menus, parties, requests, settings and uploaded files stay exactly as they are.</p>
  </div>
</div>

<div class="card">
  <h2>This site</h2>
  <p>Live now: <strong><?= e($fmt($current)) ?></strong><?php if (!empty($current['updated'])): ?> <span class="muted">installed <?= e(fmt_datetime(date('Y-m-d H:i:s', (int) strtotime((string) $current['updated'])))) ?></span><?php endif; ?></p>
<?php if ($check === null): ?>
  <p class="muted" style="margin-top:8px">Set the repository below to start receiving updates.</p>
<?php elseif (!$check['ok']): ?>
  <p class="note bad" style="margin-top:14px"><?= e($check['error']) ?></p>
<?php elseif (!$check['available']): ?>
  <p class="note ok" style="margin-top:14px">The website is up to date.</p>
<?php else: ?>
  <p class="note warn" style="margin-top:14px"><strong>An update is ready:</strong> <?= e($fmt($check['latest'])) ?>. <?= e($checksText[$check['checks']]) ?></p>
<?php endif; ?>
<?php if ($check !== null): ?>
  <div class="actions">
<?php if ($check['ok'] && $check['available'] && !in_array($check['checks'], ['failed', 'running'], true)): ?>
    <form method="post" onsubmit="return confirm('Update the website now?');"><?= csrf_field() ?><input type="hidden" name="action" value="run"><button class="btn" type="submit" onclick="this.textContent='Updating';">Update website</button></form>
<?php endif; ?>
    <a class="btn ghost" href="updates.php">Check for updates</a>
  </div>
<?php endif; ?>
</div>

<div class="card">
  <h2>Roll back</h2>
<?php if ($backups): ?>
  <p class="muted">Before each update the site keeps a copy of the files it replaces (the last five). Rolling back puts the newest copy back, from <?= e(fmt_datetime(date('Y-m-d H:i:s', (int) filemtime($backups[0])))) ?>.</p>
  <form method="post" class="actions" onsubmit="return confirm('Put the site back as it was before the last update?');"><?= csrf_field() ?><input type="hidden" name="action" value="rollback"><button class="btn ghost" type="submit">Roll back last update</button></form>
<?php else: ?>
  <p class="muted">Nothing to roll back yet. Before each update the site keeps a copy of the files it replaces.</p>
<?php endif; ?>
</div>

<form class="card" method="post" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="settings">
  <h2>Where updates come from</h2>
  <div class="grid3">
    <label>Repository <span class="hint">As owner/name.</span><input name="update_repo" value="<?= e($s['repo']) ?>" placeholder="mohamedowis-alt/plus-one-website" required></label>
    <label>Branch <span class="hint">Usually main.</span><input name="update_branch" value="<?= e($s['branch']) ?>" required></label>
    <label>Access token <span class="hint"><?= $s['token'] !== '' ? 'Saved. Leave empty to keep it.' : 'Needed when the repository is private.' ?></span><input type="password" name="update_token" value="" autocomplete="new-password"></label>
<?php if ($s['token'] !== ''): ?>
    <label class="tick full"><input type="checkbox" name="forget_token"> Remove the saved token</label>
<?php endif; ?>
  </div>
  <p class="muted" style="margin-top:14px">For a private repository, create a fine-grained token on GitHub that can only read this one repository: Contents "Read-only", and Actions "Read-only" so this page can see whether a version passed its checks.</p>
  <div class="actions"><button class="btn" type="submit">Save</button></div>
</form>
<?php admin_foot();
