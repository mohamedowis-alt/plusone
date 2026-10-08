<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require_login();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;   // 0 = a new party
$errors = [];
$arabic = arabic_ready();   // the Arabic fields exist once update step 8 has run
$form = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $id = (int) post('id');

    if ($action === 'delete') {
        if ((int) val('SELECT COUNT(*) FROM menus WHERE section_id = ?', [$id]) > 0) {
            flash('This party still has menus. Delete or move them first, or just hide the party.', 'bad');
        } else {
            q('DELETE FROM sections WHERE id = ?', [$id]);
            flash('Party deleted.');
        }
        redirect('sections.php');
    }

    if ($action === 'save') {
        $form = [
            'id' => $id,
            'name' => post('name', 80),
            'sum_a' => post('sum_a', 40),
            'sum_b' => post('sum_b', 40),
            'best_for' => post('best_for', 160),
            'mark' => isset(SECTION_MARKS[post('mark')]) ? post('mark') : 'plain',
            'colour' => isset(SECTION_COLOURS[post('colour')]) ? post('colour') : 'amber',
            'sort_order' => max(0, min(999, (int) post('sort_order'))),
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ];
        if ($arabic) {
            $form += [
                'name_ar' => post('name_ar', 80), 'sum_a_ar' => post('sum_a_ar', 40),
                'sum_b_ar' => post('sum_b_ar', 40), 'best_for_ar' => post('best_for_ar', 160),
            ];
        }
        if ($form['name'] === '') {
            $errors[] = 'Give the party a name.';
        }
        if ($form['sum_a'] === '') {
            $errors[] = 'Fill in the first half of the sum.';
        }
        if (!$errors) {
            $data = $form;
            unset($data['id']);
            if ($id) {
                update('sections', $data, $id);
            } else {
                $slug = slugify($form['name']);
                $base = $slug;
                for ($n = 2; val('SELECT COUNT(*) FROM sections WHERE slug = ?', [$slug]); $n++) {
                    $slug = $base . '-' . $n;
                }
                insert('sections', ['slug' => $slug] + $data);
            }
            flash('Saved.');
            redirect('sections.php');
        }
        $editId = $id;
    }
}

$sections = rows('SELECT s.*, (SELECT COUNT(*) FROM menus m WHERE m.section_id = s.id) AS menu_count FROM sections s ORDER BY s.sort_order, s.id');

if ($editId !== null && $form === null) {
    $form = $editId ? row('SELECT * FROM sections WHERE id = ?', [$editId]) : [
        'id' => 0, 'name' => '', 'sum_a' => '', 'sum_b' => '', 'best_for' => '', 'mark' => 'plain', 'colour' => 'amber',
        'sort_order' => count($sections) + 1, 'is_visible' => 1,
        'name_ar' => '', 'sum_a_ar' => '', 'sum_b_ar' => '', 'best_for_ar' => '',
    ];
    if (!$form) {
        redirect('sections.php');
    }
}

admin_head('Parties', 'sections');
?>
<div class="page-head">
  <div>
    <h1>Parties</h1>
    <p>The kinds of party the site offers. Each one is a tile on the home page, a tab in the menus and a choice in the quote request.</p>
  </div>
  <?php if ($form === null): ?><a class="btn" href="sections.php?edit=0">Add a party</a><?php endif; ?>
</div>

<?php foreach ($errors as $err): ?><p class="note bad" role="alert"><?= e($err) ?></p><?php endforeach; ?>

<?php if ($form !== null): ?>
<form class="card" method="post" action="sections.php">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
  <h2><?= (int) $form['id'] ? 'Edit ' . e($form['name']) : 'A new party' ?></h2>
  <div class="grid3">
    <label>Name <input name="name" value="<?= e($form['name']) ?>" maxlength="80" placeholder="Brunch" required></label>
    <label>The sum, first half <span class="hint">Shown big on the tile: Fire + friends.</span><input name="sum_a" value="<?= e($form['sum_a']) ?>" maxlength="40" placeholder="Fire" required></label>
    <label>The sum, second half <span class="hint">The plus between them is drawn for you.</span><input name="sum_b" value="<?= e($form['sum_b']) ?>" maxlength="40" placeholder="friends"></label>
    <label class="full">Best for <input name="best_for" value="<?= e($form['best_for']) ?>" maxlength="160" placeholder="Gardens, rooftops, Sahel"></label>
<?php if ($arabic): ?>
    <p class="full in-arabic"><strong>In Arabic</strong> <span class="hint">Shown on the Arabic page. Leave one empty and the English shows in its place.</span></p>
    <label>Name <input name="name_ar" value="<?= e($form['name_ar'] ?? '') ?>" maxlength="80" dir="rtl" lang="ar" placeholder="باربكيو"></label>
    <label>The sum, first half <input name="sum_a_ar" value="<?= e($form['sum_a_ar'] ?? '') ?>" maxlength="40" dir="rtl" lang="ar" placeholder="نار"></label>
    <label>The sum, second half <input name="sum_b_ar" value="<?= e($form['sum_b_ar'] ?? '') ?>" maxlength="40" dir="rtl" lang="ar" placeholder="أصحاب"></label>
    <label class="full">Best for <input name="best_for_ar" value="<?= e($form['best_for_ar'] ?? '') ?>" maxlength="160" dir="rtl" lang="ar" placeholder="الحدائق، الأسطح، الساحل"></label>
<?php endif; ?>
    <label>Plus mark
      <select name="mark">
<?php foreach (SECTION_MARKS as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $form['mark'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </label>
    <label>Tile colour
      <select name="colour">
<?php foreach (SECTION_COLOURS as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $form['colour'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </label>
    <label>Order <input type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>" min="0" max="999"></label>
    <label class="tick full"><input type="checkbox" name="is_visible" <?= (int) $form['is_visible'] ? 'checked' : '' ?>> Show this party on the site</label>
  </div>
  <div class="actions">
    <button class="btn" type="submit">Save</button>
    <a href="sections.php">Cancel</a>
  </div>
</form>
<?php endif; ?>

<div class="table-wrap" style="margin-top:18px">
<table class="table">
  <thead><tr><th></th><th>Party</th><th>Tile</th><th>Menus</th><th>On the site</th><th></th></tr></thead>
  <tbody>
<?php foreach ($sections as $s): ?>
    <tr>
      <td><span class="section-swatch c-<?= e($s['colour']) ?>"><?= mark($s['mark']) ?></span></td>
      <td><strong><?= e($s['name']) ?></strong><?php if (($s['name_ar'] ?? '') !== ''): ?> <span class="muted" dir="rtl" lang="ar"><?= e($s['name_ar']) ?></span><?php endif; ?><br><span class="muted"><?= e($s['best_for']) ?></span></td>
      <td><?= e($s['sum_a']) ?><?= $s['sum_b'] !== '' ? ' + ' . e($s['sum_b']) : '' ?></td>
      <td><a href="menus.php"><?= (int) $s['menu_count'] ?></a></td>
      <td><span class="pill <?= (int) $s['is_visible'] ? 's-won' : 'off' ?>"><?= (int) $s['is_visible'] ? 'Showing' : 'Hidden' ?></span></td>
      <td>
        <div class="row">
          <a href="sections.php?edit=<?= (int) $s['id'] ?>"><strong>Edit</strong></a>
          <form method="post" onsubmit="return confirm('Delete this party?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="delete"><button class="linkbtn danger" type="submit">Delete</button></form>
        </div>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php admin_foot();
