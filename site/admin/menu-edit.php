<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require_login();

$sections = rows('SELECT * FROM sections ORDER BY sort_order, id');
if (!$sections) {
    flash('Add a party first, then give it a menu.', 'warn');
    redirect('sections.php');
}

$id = (int) ($_GET['id'] ?? 0);
$menu = $id ? row('SELECT * FROM menus WHERE id = ?', [$id]) : null;
if ($id && !$menu) {
    flash('That menu no longer exists.', 'bad');
    redirect('menus.php');
}
$menu ??= [
    'id' => 0, 'section_id' => (int) ($_GET['section'] ?? $sections[0]['id']), 'title' => '', 'season' => '', 'intro' => '',
    'image' => '', 'pdf' => '', 'is_published' => 1, 'is_sample' => 0, 'sort_order' => 1,
];
$arabic = arabic_ready();   // the Arabic fields exist once update step 8 has run
$menu += ['title_ar' => '', 'season_ar' => '', 'intro_ar' => ''];
$dishes = $id ? rows('SELECT * FROM dishes WHERE menu_id = ? ORDER BY sort_order, id', [$id]) : [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // An upload bigger than the server allows arrives with an empty form.
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Those files are too big for this server. The limit is ' . upload_limit_text() . ' in total.', 'bad');
        redirect('menu-edit.php' . ($id ? '?id=' . $id : ''));
    }
    csrf_check();

    $sectionIds = array_map('intval', array_column($sections, 'id'));
    $menu['section_id'] = in_array((int) post('section_id'), $sectionIds, true) ? (int) post('section_id') : $sectionIds[0];
    $menu['title'] = post('title', 160);
    $menu['season'] = post('season', 80);
    $menu['intro'] = post('intro', 1000);
    $menu['title_ar'] = post('title_ar', 160);
    $menu['season_ar'] = post('season_ar', 80);
    $menu['intro_ar'] = post('intro_ar', 1000);
    $menu['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $menu['sort_order'] = max(0, min(999, (int) post('sort_order')));

    $dishes = [];
    $names = is_array($_POST['d_name'] ?? null) ? $_POST['d_name'] : [];
    foreach ($names as $i => $name) {
        $name = mb_substr(trim((string) $name), 0, 160);
        if ($name === '') {
            continue;
        }
        $tag = (string) ($_POST['d_tag'][$i] ?? '');
        $dish = [
            'course' => mb_substr(trim((string) ($_POST['d_course'][$i] ?? '')), 0, 80),
            'name' => $name,
            'description' => mb_substr(trim((string) ($_POST['d_desc'][$i] ?? '')), 0, 400),
            'tag' => isset(DISH_TAGS[$tag]) ? $tag : '',
        ];
        if ($arabic) {
            $dish += [
                'course_ar' => mb_substr(trim((string) ($_POST['d_course_ar'][$i] ?? '')), 0, 80),
                'name_ar' => mb_substr(trim((string) ($_POST['d_name_ar'][$i] ?? '')), 0, 160),
                'description_ar' => mb_substr(trim((string) ($_POST['d_desc_ar'][$i] ?? '')), 0, 400),
            ];
        }
        $dishes[] = $dish;
    }

    if ($menu['title'] === '') {
        $errors[] = 'Give the menu a name.';
    }
    $image = save_upload('image', 'image');
    $pdf = save_upload('pdf', 'pdf');
    foreach ([$image, $pdf] as $u) {
        if ($u['error'] !== '') {
            $errors[] = $u['error'];
        }
    }
    if (!$dishes && $pdf['file'] === '' && $menu['pdf'] === '') {
        $errors[] = 'Add at least one dish, or upload the menu as a PDF.';
    }

    if ($errors) {
        delete_upload($image['file']);
        delete_upload($pdf['file']);
    } else {
        if ($image['file'] !== '' || isset($_POST['remove_image'])) {
            delete_upload($menu['image']);
            $menu['image'] = $image['file'];
        }
        if ($pdf['file'] !== '' || isset($_POST['remove_pdf'])) {
            delete_upload($menu['pdf']);
            $menu['pdf'] = $pdf['file'];
        }
        $data = [
            'section_id' => $menu['section_id'], 'title' => $menu['title'], 'season' => $menu['season'],
            'intro' => $menu['intro'], 'image' => $menu['image'], 'pdf' => $menu['pdf'],
            'is_published' => $menu['is_published'], 'is_sample' => 0, 'sort_order' => $menu['sort_order'],
            'updated_at' => now(),
        ];
        if ($arabic) {
            $data += ['title_ar' => $menu['title_ar'], 'season_ar' => $menu['season_ar'], 'intro_ar' => $menu['intro_ar']];
        }
        if ($id) {
            update('menus', $data, $id);
        } else {
            $id = insert('menus', $data);
        }
        q('DELETE FROM dishes WHERE menu_id = ?', [$id]);
        foreach ($dishes as $n => $d) {
            insert('dishes', ['menu_id' => $id] + $d + ['sort_order' => $n + 1]);
        }

        $all = $menu['title'] . ' ' . $menu['intro'] . ' ' . $menu['title_ar'] . ' ' . $menu['intro_ar'];
        foreach ($dishes as $d) {
            $all .= ' ' . $d['name'] . ' ' . $d['description'] . ' ' . ($d['name_ar'] ?? '') . ' ' . ($d['description_ar'] ?? '');
        }
        if (looks_like_price($all)) {
            flash('Saved. One thing: something in this menu looks like a price. The site shows menus without prices, so take it out if it is one.', 'warn');
        } else {
            flash($menu['is_published'] ? 'Saved. The menu is on the site.' : 'Saved. The menu is hidden until you tick "Show on the site".');
        }
        redirect('menu-edit.php?id=' . $id);
    }
}

// Always offer a few empty rows to type into.
$blank = ['course' => '', 'name' => '', 'description' => '', 'tag' => '', 'course_ar' => '', 'name_ar' => '', 'description_ar' => ''];
$rowsToShow = $dishes ?: [$blank, $blank, $blank];

$dishRow = function (array $d) use ($arabic): string {
    $opts = '';
    foreach (DISH_TAGS as $key => [$label]) {
        $opts .= '<option value="' . e($key) . '"' . ($d['tag'] === $key ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return '<div class="dish-row">'
        . '<span class="grab"><button type="button" data-move="-1" aria-label="Move up">&#9650;</button><button type="button" data-move="1" aria-label="Move down">&#9660;</button></span>'
        . '<input name="d_course[]" value="' . e($d['course']) . '" placeholder="To start" aria-label="Course" maxlength="80">'
        . '<input name="d_name[]" value="' . e($d['name']) . '" placeholder="Garden leaves" aria-label="Dish" maxlength="160">'
        . '<input name="d_desc[]" value="' . e($d['description']) . '" placeholder="Picked this week and dressed at the table." aria-label="One line about it" maxlength="400">'
        . '<select name="d_tag[]" aria-label="Source tag">' . $opts . '</select>'
        . '<button type="button" class="del" data-del aria-label="Remove this dish">&times;</button>'
        . ($arabic
            ? '<input class="ar ar-course" name="d_course_ar[]" value="' . e($d['course_ar'] ?? '') . '" placeholder="للبداية" aria-label="Course, in Arabic" maxlength="80" dir="rtl" lang="ar">'
            . '<input class="ar ar-name" name="d_name_ar[]" value="' . e($d['name_ar'] ?? '') . '" placeholder="ورقيات الحديقة" aria-label="Dish, in Arabic" maxlength="160" dir="rtl" lang="ar">'
            . '<input class="ar ar-desc" name="d_desc_ar[]" value="' . e($d['description_ar'] ?? '') . '" placeholder="من قطاف هذا الأسبوع، تُتبَّل على المائدة." aria-label="One line about it, in Arabic" maxlength="400" dir="rtl" lang="ar">'
            : '')
        . '</div>';
};

admin_head($id ? 'Edit menu' : 'Add a menu', 'menus');
?>
<div class="page-head">
  <div>
    <p><a href="menus.php">All menus</a></p>
    <h1><?= $id ? 'Edit menu' : 'Add a menu' ?></h1>
  </div>
  <?php if ($id && (int) $menu['is_published']): ?><a class="btn ghost small" href="../index.php#menus" target="_blank" rel="noopener">See it on the site</a><?php if ($arabic): ?> <a class="btn ghost small" href="../index.php?lang=ar#menus" target="_blank" rel="noopener">See it in Arabic</a><?php endif; ?><?php endif; ?>
</div>

<?php foreach ($errors as $err): ?><p class="note bad" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<?php if ((int) $menu['is_sample']): ?><p class="note warn">This menu is a first draft and has not been reviewed by the kitchen. Check every dish, description and source tag. Saving it confirms it.</p><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card">
    <h2>The menu</h2>
    <div class="grid3">
      <label>Party
        <select name="section_id">
<?php foreach ($sections as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= (int) $menu['section_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
<?php endforeach; ?>
        </select>
      </label>
      <label>Menu name <input name="title" value="<?= e($menu['title']) ?>" maxlength="160" placeholder="The autumn table" required></label>
      <label>Season <input name="season" value="<?= e($menu['season']) ?>" maxlength="80" placeholder="Autumn 2026"></label>
      <label class="full">A line or two about it <span class="hint">Plain words. No prices.</span>
        <textarea name="intro" maxlength="1000" rows="2"><?= e($menu['intro']) ?></textarea>
      </label>
<?php if ($arabic): ?>
      <p class="full in-arabic"><strong>In Arabic</strong> <span class="hint">Shown on the Arabic page. Leave one empty and the English shows in its place.</span></p>
      <label>Menu name <input name="title_ar" value="<?= e($menu['title_ar']) ?>" maxlength="160" dir="rtl" lang="ar" placeholder="مائدة الخريف"></label>
      <label>Season <input name="season_ar" value="<?= e($menu['season_ar']) ?>" maxlength="80" dir="rtl" lang="ar" placeholder="خريف 2026"></label>
      <label class="full">A line or two about it
        <textarea name="intro_ar" maxlength="1000" rows="2" dir="rtl" lang="ar"><?= e($menu['intro_ar']) ?></textarea>
      </label>
<?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>Dishes</h2>
    <p class="muted" style="margin-bottom:14px">One row per dish. "Course" groups dishes under a small heading, such as To start or From the grill. Give a dish a source tag only when it is true for that dish.<?php if ($arabic): ?> The second line of each dish is its Arabic. Leave it empty and the Arabic page shows the English.<?php endif; ?></p>
    <div class="dish-head"><span></span><span>Course</span><span>Dish</span><span>One line about it</span><span>Source tag</span><span></span></div>
    <div class="dishes<?= $arabic ? ' with-arabic' : '' ?>" id="dishes">
<?php foreach ($rowsToShow as $d): ?>
      <?= $dishRow($d) ?>
<?php endforeach; ?>
    </div>
    <div class="actions"><button type="button" class="btn ghost small" id="add-dish">Add a dish</button></div>
    <template id="dish-template"><?= $dishRow($blank) ?></template>
  </div>

  <div class="card">
    <h2>Picture and PDF</h2>
    <p class="muted" style="margin-bottom:14px">Both optional. Files up to <?= e(upload_limit_text()) ?>. If you upload a designed menu as a PDF, check that it has no prices on it.</p>
    <div class="grid2">
      <div>
        <label>Picture <span class="hint">JPG, PNG or WebP. Shown beside the dishes.</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
<?php if ($menu['image'] !== ''): ?>
        <img class="thumb" src="../uploads/menus/<?= e($menu['image']) ?>" alt="">
        <label class="tick" style="margin-top:8px"><input type="checkbox" name="remove_image"> Remove this picture</label>
<?php endif; ?>
      </div>
      <div>
        <label>Menu as a PDF <span class="hint">Guests get a "Download the menu" button.</span><input type="file" name="pdf" accept="application/pdf"></label>
<?php if ($menu['pdf'] !== ''): ?>
        <p style="margin-top:8px"><a href="../uploads/menus/<?= e($menu['pdf']) ?>" target="_blank" rel="noopener"><strong>Open the current PDF</strong></a></p>
        <label class="tick" style="margin-top:8px"><input type="checkbox" name="remove_pdf"> Remove this PDF</label>
<?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <h2>On the site</h2>
    <div class="grid2">
      <label class="tick"><input type="checkbox" name="is_published" <?= (int) $menu['is_published'] ? 'checked' : '' ?>> Show on the site</label>
      <label>Order <span class="hint">When a party has several menus, lower numbers come first.</span><input type="number" name="sort_order" value="<?= (int) $menu['sort_order'] ?>" min="0" max="999"></label>
    </div>
  </div>

  <div class="actions">
    <button class="btn" type="submit">Save the menu</button>
    <a href="menus.php">Cancel</a>
  </div>
</form>

<script>
(function () {
  var list = document.getElementById('dishes');
  var tpl = document.getElementById('dish-template');
  document.getElementById('add-dish').addEventListener('click', function () {
    var rows = list.querySelectorAll('.dish-row');
    var node = tpl.content.firstElementChild.cloneNode(true);
    // Carry the course down, since dishes usually come in groups.
    if (rows.length) {
      ['d_course[]', 'd_course_ar[]'].forEach(function (name) {
        var from = rows[rows.length - 1].querySelector('[name="' + name + '"]');
        if (from) { node.querySelector('[name="' + name + '"]').value = from.value; }
      });
    }
    list.appendChild(node);
    node.querySelector('[name="d_name[]"]').focus();
  });
  list.addEventListener('click', function (ev) {
    var row = ev.target.closest('.dish-row');
    if (!row) { return; }
    if (ev.target.closest('[data-del]')) { row.remove(); return; }
    var mover = ev.target.closest('[data-move]');
    if (mover) {
      if (mover.getAttribute('data-move') === '-1' && row.previousElementSibling) { list.insertBefore(row, row.previousElementSibling); }
      if (mover.getAttribute('data-move') === '1' && row.nextElementSibling) { list.insertBefore(row.nextElementSibling, row); }
      mover.focus();
    }
  });
})();
</script>
<?php admin_foot();
