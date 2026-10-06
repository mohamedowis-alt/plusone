<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) post('id');
    $m = row('SELECT * FROM menus WHERE id = ?', [$id]);
    if ($m) {
        $action = post('action');
        if ($action === 'toggle') {
            q('UPDATE menus SET is_published = ?, updated_at = ? WHERE id = ?', [(int) !$m['is_published'], now(), $id]);
            flash($m['is_published'] ? 'Menu taken off the site.' : 'Menu is now on the site.');
        } elseif ($action === 'delete') {
            delete_upload($m['image']);
            delete_upload($m['pdf']);
            q('DELETE FROM dishes WHERE menu_id = ?', [$id]);
            q('DELETE FROM menus WHERE id = ?', [$id]);
            flash('Menu deleted.');
        }
    }
    redirect('menus.php');
}

$sections = rows('SELECT * FROM sections ORDER BY sort_order, id');
$menus = rows('SELECT m.*, (SELECT COUNT(*) FROM dishes d WHERE d.menu_id = m.id) AS dish_count FROM menus m ORDER BY m.sort_order, m.id');
$bySection = [];
foreach ($menus as $m) {
    $bySection[$m['section_id']][] = $m;
}
$samples = count(array_filter($menus, fn ($m) => (int) $m['is_sample'] === 1));

admin_head('Menus', 'menus');
?>
<div class="page-head">
  <div>
    <h1>Seasonal menus</h1>
    <p>One or more menus for each party. The site shows them without prices. When the season changes, add the new menu and take the old one off the site.</p>
  </div>
  <a class="btn" href="menu-edit.php">Add a menu</a>
</div>

<?php if ($samples > 0): ?>
<p class="note warn"><strong><?= $samples ?> <?= $samples === 1 ? 'menu is' : 'menus are' ?> waiting for the kitchen's review.</strong> They were written as a first draft. Check every dish, description and source tag, change what needs changing, then save the menu to confirm it.</p>
<?php endif; ?>

<?php foreach ($sections as $s): ?>
<div class="card">
  <div class="row spread">
    <div class="row">
      <span class="section-swatch c-<?= e($s['colour']) ?>"><?= mark($s['mark']) ?></span>
      <strong style="font-size:20px"><?= e($s['name']) ?></strong>
      <?php if (!(int) $s['is_visible']): ?><span class="pill off">Hidden party</span><?php endif; ?>
    </div>
    <a class="btn ghost small" href="menu-edit.php?section=<?= (int) $s['id'] ?>">Add <?= preg_match('/^[aeiou]/i', $s['name']) ? 'an' : 'a' ?> <?= e(strtolower($s['name'])) ?> menu</a>
  </div>
<?php if (empty($bySection[$s['id']])): ?>
  <p class="muted" style="margin-top:12px">No menu yet. The site says the new menu is on its way.</p>
<?php else: ?>
  <table class="table" style="margin-top:14px">
    <thead><tr><th>Menu</th><th>Season</th><th>Dishes</th><th>On the site</th><th></th></tr></thead>
    <tbody>
<?php foreach ($bySection[$s['id']] as $m): ?>
      <tr>
        <td><a class="strong" href="menu-edit.php?id=<?= (int) $m['id'] ?>"><?= e($m['title']) ?></a> <?php if ((int) $m['is_sample']): ?><span class="pill sample">To review</span><?php endif; ?></td>
        <td><?= e($m['season']) ?></td>
        <td><?= (int) $m['dish_count'] ?><?= $m['image'] !== '' ? ', picture' : '' ?><?= $m['pdf'] !== '' ? ', PDF' : '' ?></td>
        <td><span class="pill <?= (int) $m['is_published'] ? 's-won' : 'off' ?>"><?= (int) $m['is_published'] ? 'Showing' : 'Hidden' ?></span></td>
        <td>
          <div class="row">
            <a href="menu-edit.php?id=<?= (int) $m['id'] ?>"><strong>Edit</strong></a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="linkbtn" type="submit"><?= (int) $m['is_published'] ? 'Hide' : 'Show' ?></button></form>
            <form method="post" onsubmit="return confirm('Delete this menu for good?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="linkbtn danger" type="submit">Delete</button></form>
          </div>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php admin_foot();
