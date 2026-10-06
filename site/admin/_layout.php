<?php
declare(strict_types=1);

// Shared frame for every admin page.

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/uploads.php';
if (!installed()) {
    redirect('../install.php');
}

function admin_head(string $title, string $active): void
{
    $user = current_user();
    $new = (int) val("SELECT COUNT(*) FROM requests WHERE status = 'new'");
    $nav = [
        'requests' => ['index.php', 'Requests'],
        'menus'    => ['menus.php', 'Menus'],
        'sections' => ['sections.php', 'Parties'],
        'settings' => ['settings.php', 'Settings'],
        'team'     => ['team.php', 'Team'],
    ];
    $flash = flash();
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> | +1 admin</title>
<link rel="icon" href="../assets/img/plus-one-symbol-colour.svg" type="image/svg+xml">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700;800&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css', '../')) ?>">
</head>
<body>
<div class="shell">
  <aside class="side">
    <a href="index.php" aria-label="+1 admin"><?= logo('inline') ?></a>
    <nav aria-label="Admin">
<?php foreach ($nav as $key => [$href, $label]): ?>
      <a href="<?= e($href) ?>" class="<?= $key === $active ? 'on' : '' ?>"><?= e($label) ?><?php if ($key === 'requests' && $new > 0): ?> <span class="n"><?= $new ?></span><?php endif; ?></a>
<?php endforeach; ?>
    </nav>
    <div class="who">
      <span><?= e($user['name'] ?? '') ?></span>
      <a href="../index.php" target="_blank" rel="noopener">See the site</a>
      <form method="post" action="logout.php"><?= csrf_field() ?><button class="linkbtn" type="submit">Sign out</button></form>
      <?php if (site_version() !== ''): ?><span class="version">Version <?= e(site_version()) ?></span><?php endif; ?>
    </div>
  </aside>
  <main class="main">
<?php if ($flash): ?>
    <p class="note <?= e($flash['kind']) ?>" role="status"><?= e($flash['message']) ?></p>
<?php endif;
}

function admin_foot(): void
{
    ?>
  </main>
</div>
</body>
</html>
<?php
}
