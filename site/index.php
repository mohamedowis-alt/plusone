<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
if (!installed()) {
    redirect('install.php');
}

$sections = public_sections();
$wa = wa_number((string) setting('whatsapp', ''));
$waLink = $wa !== '' ? 'https://wa.me/' . $wa : '';
$ig = ltrim((string) setting('instagram', ''), '@');

$sent = (string) ($_GET['sent'] ?? '');
$sent = preg_match('/^P1-\d{4,8}$/', $sent) ? $sent : '';
$problem = mb_substr((string) ($_GET['problem'] ?? ''), 0, 300);

$confetti = function (array $bits): string {
    $out = '';
    foreach ($bits as $b) {
        $out .= '<svg viewBox="0 0 100 100" style="--x:' . $b[0] . ';--y:' . $b[1] . ';--s:' . $b[2] . 'px;--rot:' . $b[3]
            . 'deg;--c:' . $b[4] . ';--d:' . ($b[5] ?? 0) . 's"><path d="' . PLUS_D . '"/></svg>';
    }
    return $out;
};

$moods = [
    'heart'  => 'Love changes the food',
    'sprout' => 'Locally grown',
    'steam'  => 'Cooked to order',
    'slice'  => 'From our own butchery',
    'check'  => 'Home made',
    'bite'   => 'We eat it too',
];

$steps = ['The occasion', 'The guests', 'The party', 'The service', 'When and where', 'The vibe', 'Good to know', 'You'];
?><!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>+1 by RDNA | Catering and events in Cairo</title>
<meta name="description" content="Bring us as your plus one. Catering for gatherings at home and at work, with local ingredients from RDNA, made with love.">
<meta property="og:title" content="+1 by RDNA. Bring us as your plus one.">
<meta property="og:description" content="Local ingredients from RDNA. Made with love. Shared with the people you gather.">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= e(site_url()) ?>assets/img/share.png">
<meta name="theme-color" content="#F6B11A">
<link rel="icon" href="assets/img/plus-one-symbol-colour.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;700;800&amp;display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body>
<!--page-->
<a class="skip" href="#main">Skip to the content</a>

<header class="top">
  <div class="wrap">
    <a class="brand" href="#top" aria-label="+1 by RDNA, top of the page"><?= logo('inline') ?></a>
    <nav aria-label="Sections">
      <a href="#promise">The promise</a>
      <a href="#gather">Gatherings</a>
      <a href="#parties">Parties</a>
      <a href="#menus">Menus</a>
    </nav>
    <a class="btn btn-ink small" href="#quote">Get a quote</a>
  </div>
</header>

<main id="main">

<section class="hero on-amber" id="top">
  <div class="confetti" aria-hidden="true"><?= $confetti([
      ['1.5%', '86%', 44, -14, '#FBF7C6', 0],
      ['44%', '8%', 30, 12, '#000000', 1.2],
      ['93%', '12%', 52, 16, '#FBF7C6', 2.1],
      ['50%', '84%', 26, -8, '#783B8D', .6],
      ['90%', '80%', 34, 22, '#000000', 3],
  ]) ?></div>
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <p class="eyebrow">Catering &amp; events</p>
      <h1>Bring us<br>as your<br>plus one.</h1>
      <p class="lede">Local ingredients from RDNA. Made with love. Shared with the people you gather.</p>
      <div class="actions">
        <a class="btn btn-ink" href="#quote">Plan your gathering</a>
        <a class="btn" href="#menus">See this season's menus</a>
      </div>
    </div>
    <div class="hero-plus">
      <button type="button" class="bigplus" id="bigplus" aria-label="Change the plus. Each one stands for a promise.">
<?php $first = true; foreach ($moods as $m => $label): ?>
        <?= mark($m, $first ? 'is-on' : '', '-10 -40 120 150') ?>
<?php $first = false; endforeach; ?>
      </button>
      <p class="mood" aria-live="polite"><span id="mood-label"><?= e(reset($moods)) ?></span></p>
      <p class="mood-hint js-only">Tap the plus</p>
    </div>
  </div>
</section>

<section id="promise">
  <div class="wrap">
    <div class="head">
      <p class="eyebrow">The promise</p>
      <h2>Local ingredients.<br>Made with love.</h2>
      <p class="lede">You know RDNA from its stores. +1 brings the same ingredients to your gathering, cooked by the people who produce them.</p>
    </div>
    <ul class="tiles">
      <li class="tile on-green"><?= mark('sprout') ?><h3>We grow it</h3><p>Produce from RDNA's own land, or from a farm we name.</p></li>
      <li class="tile on-red"><?= mark('slice') ?><h3>We butcher it</h3><p>Meat raised by us and cut in our own butchery.</p></li>
      <li class="tile on-yellow"><?= mark('check') ?><h3>We make it</h3><p>From scratch. Everything is fresh. No powder, no chemical.</p></li>
      <li class="tile on-purple"><?= mark('steam') ?><h3>We cook it</h3><p>To order, at your event, in front of your guests.</p></li>
    </ul>
  </div>
</section>

<div class="love">
  <div class="wrap">
    <?= mark('heart') ?>
    <div>
      <h2>Love changes<br>the food.</h2>
      <p>The same people grow it, cut it and cook it. We feed our guests what we feed our children.</p>
    </div>
  </div>
</div>

<section id="gather">
  <div class="wrap">
    <div class="gather-grid">
      <div>
        <div class="head" style="margin-bottom:0">
          <p class="eyebrow">The gathering</p>
          <h2><?= sum('Good people', 'good food') ?></h2>
          <p class="lede">A table is where people meet. We bring food that gets them talking: cooked in front of them, served to share, made with love.</p>
        </div>
        <div class="two">
          <div><h3>At home</h3><p>Birthdays, iftars, engagements, a Friday with friends. You stay with your guests. We look after the table.</p></div>
          <div><h3>At work</h3><p>Coffee breaks, lunches, team days, client evenings. Food people leave their desks for, and a reason to sit together.</p></div>
        </div>
      </div>
      <div class="gather-pic">
        <picture>
          <source srcset="assets/img/buffet-table.webp" type="image/webp">
          <img src="assets/img/buffet-table.jpg" width="1320" height="800" loading="lazy" alt="A +1 buffet table seen from above: an amber runner patterned with plus signs, bowls each topped with a plus, and a card on every dish.">
        </picture>
      </div>
    </div>
    <ul class="three">
      <li><?= mark('steam') ?><div><h3>Live cooking</h3><p>A station your guests gather around.</p></div></li>
      <li><?= mark('bite') ?><div><h3>Food to share</h3><p>Big dishes in the middle of the table.</p></div></li>
      <li><?= mark('heart') ?><div><h3>A team that hosts</h3><p>People who look after your guests like their own.</p></div></li>
    </ul>
    <div class="ways">
      <p class="label">However you gather</p>
      <div><h3><?= plus() ?>Box</h3><p>Dropped at your door, ready to serve.</p></div>
      <div><h3><?= plus() ?>Table</h3><p>Set up and served by our team.</p></div>
      <div><h3><?= plus() ?>Hosted</h3><p>The whole event, start to finish.</p></div>
    </div>
  </div>
</section>

<div class="strip" aria-hidden="true"></div>

<section id="parties">
  <div class="wrap">
    <div class="head">
      <p class="eyebrow">Choose your party</p>
      <h2><?= count($sections) === 6 ? 'Six parties,' : 'Parties,' ?><br>ready to go</h2>
      <p class="lede">Ways to bring people together. Each has its own menu, and each can be shaped around your guests.</p>
    </div>
    <div class="parties-grid">
<?php foreach ($sections as $s): ?>
      <a class="party on-<?= e($s['colour']) ?>" href="#menus" data-tab="<?= e($s['slug']) ?>">
        <?= mark($s['mark']) ?>
        <span class="name"><?= e($s['name']) ?></span>
        <span class="what"><?= $s['sum_b'] !== '' ? sum($s['sum_a'], $s['sum_b']) : e($s['sum_a']) ?></span>
        <span class="best"><?= e($s['best_for']) ?></span>
        <span class="go">See the menu</span>
      </a>
<?php endforeach; ?>
    </div>
    <p class="else">Planning something else? An engagement, a graduation, a company day. <a href="#quote">Ask us.</a></p>
  </div>
</section>

<section class="menus" id="menus">
  <div class="wrap">
    <div class="head">
      <p class="eyebrow">Know your source</p>
      <h2>This season's<br>menus</h2>
      <p class="lede">Every dish says where it came from. Menus change with the season, and each one can be shaped around your guests.</p>
    </div>
<?php if ($sections): ?>
    <div class="tabs js-only" role="tablist" aria-label="Parties">
<?php foreach ($sections as $i => $s): ?>
      <button class="tab" type="button" role="tab" id="tab-<?= e($s['slug']) ?>" aria-controls="panel-<?= e($s['slug']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" <?= $i === 0 ? '' : 'tabindex="-1"' ?>><?= e($s['name']) ?></button>
<?php endforeach; ?>
    </div>
<?php foreach ($sections as $i => $s): ?>
    <div class="panel" role="tabpanel" id="panel-<?= e($s['slug']) ?>" aria-labelledby="tab-<?= e($s['slug']) ?>" <?= $i === 0 ? '' : 'hidden' ?>>
      <p class="panel-name"><?= e($s['name']) ?></p>
<?php if (!$s['menus']): ?>
      <p class="empty">The new <?= e(strtolower($s['name'])) ?> menu is on its way. <a href="#quote" data-party="<?= e($s['name']) ?>"><strong>Ask us</strong></a> and we will send it to you.</p>
<?php endif; ?>
<?php foreach ($s['menus'] as $m): ?>
      <article class="menu">
        <header class="menu-head c-<?= e($s['colour']) ?>">
          <div>
            <?php if ($m['season'] !== ''): ?><span class="season"><?= e($m['season']) ?></span><?php endif; ?>
            <h3><?= e($m['title']) ?></h3>
            <?php if ($m['intro'] !== ''): ?><p><?= nl2br(e($m['intro'])) ?></p><?php endif; ?>
          </div>
          <?= mark($s['mark']) ?>
        </header>
        <div class="menu-body<?= $m['image'] !== '' ? ' has-pic' : '' ?>">
          <div class="courses">
<?php $course = null; foreach ($m['dishes'] as $d): ?>
<?php if ($d['course'] !== $course && $d['course'] !== ''): $course = $d['course']; ?>
            <p class="course"><?= e($course) ?></p>
<?php endif; ?>
            <div class="dish">
              <div><b><?= e($d['name']) ?></b><?php if ($d['description'] !== ''): ?><span class="d"><?= e($d['description']) ?></span><?php endif; ?></div>
              <?php if (isset(DISH_TAGS[$d['tag']]) && $d['tag'] !== ''): ?><span class="tag t-<?= e(DISH_TAGS[$d['tag']][2]) ?>"><?= plus() ?><?= e(DISH_TAGS[$d['tag']][0]) ?></span><?php endif; ?>
            </div>
<?php endforeach; ?>
          </div>
<?php if ($m['image'] !== ''): ?>
          <div class="menu-pic"><img src="uploads/menus/<?= e($m['image']) ?>" alt="<?= e($m['title']) ?>" loading="lazy"></div>
<?php endif; ?>
        </div>
        <footer class="menu-foot">
          <p>Shaped around your guests, and priced once we know your gathering.</p>
          <div class="links">
            <?php if ($m['pdf'] !== ''): ?><a class="btn small" href="uploads/menus/<?= e($m['pdf']) ?>" target="_blank" rel="noopener">Download the menu</a><?php endif; ?>
            <a class="btn btn-ink small" href="#quote" data-party="<?= e($s['name']) ?>">Ask for this party</a>
          </div>
        </footer>
      </article>
<?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php endif; ?>
  </div>
</section>

<section class="quote on-purple" id="quote">
  <svg class="bg" viewBox="0 0 100 100" aria-hidden="true"><path d="<?= PLUS_D ?>"/></svg>
  <div class="wrap">
    <div class="quote-copy">
      <p class="eyebrow">Get a quote</p>
      <h2>Plan your<br>gathering</h2>
      <p class="lede">Eight quick questions, about a minute. We come back with a menu made for your gathering, and where every dish comes from.</p>
<?php if ($waLink !== ''): ?>
      <p class="direct">Rather talk it through? <a href="<?= e($waLink) ?>" target="_blank" rel="noopener">WhatsApp <?= e(phone_display($wa)) ?></a></p>
<?php endif; ?>
    </div>

<?php if ($sent !== ''): ?>
    <div class="wizard">
      <div class="wiz-done">
        <?= mark('heart') ?>
        <h3>Request sent</h3>
        <p>Thank you. We will come back to you with a menu made for your gathering.</p>
        <p class="ref">Your reference: <?= e($sent) ?></p>
        <?php if ($waLink !== ''): ?><a class="btn btn-ink" href="<?= e($waLink . '?text=' . rawurlencode('Hello +1, I just sent a request on your website (' . $sent . ').')) ?>" target="_blank" rel="noopener">Say hello on WhatsApp</a><?php endif; ?>
      </div>
    </div>
<?php else: ?>
    <form class="wizard" id="wizard" method="post" action="quote.php" novalidate>
      <input type="hidden" name="stamp" value="<?= e(form_stamp()) ?>">
      <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <div class="wiz-top js-only">
        <ol class="wiz-progress" aria-hidden="true">
<?php foreach ($steps as $s): ?>
          <li><?= plus('') ?></li>
<?php endforeach; ?>
        </ol>
        <p class="wiz-sum" id="wiz-sum" aria-live="polite"></p>
      </div>
<?php if ($problem !== ''): ?>
      <p class="wiz-error" role="alert"><?= e($problem) ?></p>
<?php endif; ?>

      <fieldset class="step is-on" data-step="occasion">
        <legend>What brings everyone together?</legend>
        <p class="help">Pick the one that is closest.</p>
        <div class="seg">
          <label class="chip"><input type="radio" name="setting" value="home" checked><span>At home</span></label>
          <label class="chip"><input type="radio" name="setting" value="work"><span>At work</span></label>
        </div>
<?php foreach (EVENT_TYPES as $key => $types): ?>
        <div class="q" data-setting="<?= e($key) ?>">
          <span class="lbl no-js-only"><?= e(SETTINGS_LABELS[$key]) ?></span>
          <div class="chips">
<?php foreach ($types as $t): ?>
            <label class="chip"><input type="radio" name="event_type" value="<?= e($t) ?>"><span><?= e($t) ?></span></label>
<?php endforeach; ?>
          </div>
        </div>
<?php endforeach; ?>
      </fieldset>

      <fieldset class="step" data-step="guests">
        <legend>How many guests?</legend>
        <p class="help">A good guess is fine. It can change later.</p>
        <div class="stepper">
          <button type="button" class="js-only" data-add="-5" aria-label="Five fewer guests"><svg viewBox="0 0 100 100" aria-hidden="true"><rect x="4" y="38" width="92" height="24" rx="5"/></svg></button>
          <input type="number" name="guests" id="guests" value="30" min="1" max="5000" inputmode="numeric" aria-label="Number of guests">
          <button type="button" class="js-only" data-add="5" aria-label="Five more guests"><?= plus('') ?></button>
        </div>
        <div class="q js-only">
          <div class="chips" id="guest-presets">
<?php foreach ([10, 20, 40, 80, 150, 300] as $n): ?>
            <button type="button" class="tab" data-guests="<?= $n ?>"><?= $n ?></button>
<?php endforeach; ?>
          </div>
        </div>
      </fieldset>

      <fieldset class="step" data-step="party">
        <legend>Pick your party</legend>
        <p class="help">One, a few, or none. If you skip this we will suggest something.</p>
        <div class="cards three-up">
<?php foreach ($sections as $s): ?>
          <label class="pick"><input type="checkbox" name="parties[]" value="<?= e($s['name']) ?>"><span class="box"><b><?= mark($s['mark']) ?><?= e($s['name']) ?></b><small><?= e($s['best_for']) ?></small></span></label>
<?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="step" data-step="style">
        <legend>How should we show up?</legend>
        <p class="help">From a box at your door to the whole event.</p>
        <div class="cards">
<?php foreach (STYLES as $key => [$label, $text]): ?>
          <label class="pick"><input type="radio" name="style" value="<?= e($key) ?>" <?= $key === 'unsure' ? 'checked' : '' ?>><span class="box"><b><?= $key !== 'unsure' ? plus() : '' ?><?= e($label) ?></b><small><?= e($text) ?></small></span></label>
<?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="step" data-step="when">
        <legend>When and where?</legend>
        <p class="help">Leave the date open if it is not fixed yet.</p>
        <div class="fields">
          <label class="field"><span>Date <i>(optional)</i></span><input type="date" name="event_date" id="event_date" min="<?= e(date('Y-m-d')) ?>"></label>
          <label class="field"><span>Area</span><input type="text" name="area" list="areas" maxlength="160" placeholder="New Cairo, Zayed, Sahel" autocomplete="address-level2"></label>
          <datalist id="areas"><?php foreach (AREAS as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="q">
          <span class="lbl">Time of day</span>
          <div class="chips">
<?php foreach (TIMES_OF_DAY as $t): ?>
            <label class="chip"><input type="radio" name="time_of_day" value="<?= e($t) ?>"><span><?= e($t) ?></span></label>
<?php endforeach; ?>
          </div>
        </div>
        <div class="q">
          <span class="lbl">Indoors or out</span>
          <div class="chips">
<?php foreach (VENUES as $v): ?>
            <label class="chip"><input type="radio" name="venue" value="<?= e($v) ?>"><span><?= e($v) ?></span></label>
<?php endforeach; ?>
          </div>
        </div>
      </fieldset>

      <fieldset class="step" data-step="vibe">
        <legend>What is the vibe?</legend>
        <p class="help">It tells us how to set the table and how to serve.</p>
        <div class="chips">
<?php foreach (VIBES as $v): ?>
          <label class="chip"><input type="radio" name="vibe" value="<?= e($v) ?>"><span><?= e($v) ?></span></label>
<?php endforeach; ?>
        </div>
        <div class="q">
          <span class="lbl">Live cooking?</span>
          <div class="chips">
<?php foreach (LIVE_COOKING as $key => $label): ?>
            <label class="chip"><input type="radio" name="live_cooking" value="<?= e($key) ?>" <?= $key === 'advise' ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
<?php endforeach; ?>
          </div>
        </div>
      </fieldset>

      <fieldset class="step" data-step="notes">
        <legend>Anything we should know?</legend>
        <p class="help">All optional. Skip what does not apply.</p>
        <div class="chips">
<?php foreach (DIETARY as $d): ?>
          <label class="chip"><input type="checkbox" name="dietary[]" value="<?= e($d) ?>"><span><?= e($d) ?></span></label>
<?php endforeach; ?>
        </div>
        <div class="fields" style="margin-top:20px">
          <label class="field full"><span>Notes <i>(a theme, a favourite dish, a surprise)</i></span><textarea name="notes" maxlength="2000"></textarea></label>
          <label class="field"><span>Budget per guest <i>(if you have one in mind)</i></span><input type="text" name="budget" maxlength="160"></label>
        </div>
      </fieldset>

      <fieldset class="step" data-step="you">
        <legend>Where do we send the menu?</legend>
        <p class="help">We only use this to reply to your request.</p>
        <div class="fields">
          <label class="field"><span>Your name</span><input type="text" name="name" maxlength="120" autocomplete="name" required></label>
          <label class="field"><span>Mobile</span><input type="tel" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="01x xxxx xxxx" required></label>
          <label class="field"><span>Email <i>(optional)</i></span><input type="email" name="email" maxlength="190" autocomplete="email"></label>
          <label class="field" data-setting="work"><span>Company</span><input type="text" name="company" maxlength="160" autocomplete="organization"></label>
        </div>
        <div class="q">
          <span class="lbl">Reply by</span>
          <div class="chips">
<?php foreach (CONTACT_PREFS as $i => $c): ?>
            <label class="chip"><input type="radio" name="contact_pref" value="<?= e($c) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= e($c) ?></span></label>
<?php endforeach; ?>
          </div>
        </div>
      </fieldset>

      <p class="wiz-error js-only" id="wiz-error" role="alert" hidden></p>
      <div class="wiz-nav">
        <button type="button" class="btn btn-back js-only" id="wiz-back" hidden>Back</button>
        <button type="button" class="btn btn-ink js-only" id="wiz-next">Next</button>
        <button type="submit" class="btn btn-ink" id="wiz-send">Send my request</button>
        <span class="count js-only" id="wiz-count"></span>
      </div>
    </form>

    <div class="wizard" id="wiz-done-card" hidden>
      <div class="wiz-done" tabindex="-1" id="wiz-done">
        <?= mark('heart') ?>
        <h3>Request sent</h3>
        <p>Thank you<span id="done-name"></span>. We will come back to you with a menu made for your gathering.</p>
        <p class="ref" id="done-ref"></p>
        <a class="btn btn-ink" id="done-wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener" <?= $waLink === '' ? 'hidden' : '' ?>>Say hello on WhatsApp</a>
        <p class="preview-note" id="done-preview" hidden>This is the preview. On the live site this request is saved and emailed to the team.</p>
      </div>
    </div>
<?php endif; ?>
  </div>
</section>

</main>

<footer class="foot">
  <div class="foot-confetti" aria-hidden="true"><?= $confetti([
      ['3%', '34px', 46, -14, '#E94E4E'], ['12%', '70px', 26, 10, '#F6B11A'], ['21%', '22px', 60, -8, '#0F8863'],
      ['33%', '68px', 34, 14, '#FAED24'], ['42%', '20px', 26, -20, '#FBF7C6'], ['51%', '50px', 64, 12, '#E94E4E'],
      ['64%', '26px', 40, -10, '#F6B11A'], ['73%', '72px', 30, 18, '#0F8863'], ['82%', '24px', 52, 8, '#783B8D'],
      ['92%', '62px', 36, -6, '#FAED24'],
  ]) ?></div>
  <div class="wrap">
    <?= logo('stacked') ?>
    <p class="line">Our ingredients.<br>Your event.</p>
    <div class="contact">
<?php if ($waLink !== ''): ?>
      <a href="<?= e($waLink) ?>" target="_blank" rel="noopener">WhatsApp <?= e(phone_display($wa)) ?></a>
<?php endif; ?>
<?php if ($ig !== ''): ?>
      <a href="https://www.instagram.com/<?= e($ig) ?>/" target="_blank" rel="noopener">Instagram @<?= e($ig) ?></a>
<?php endif; ?>
      <a href="#quote">Get a quote</a>
    </div>
    <p class="small"><span>+1 by RDNA. Catering and events, Cairo.</span><span>Bring us as your plus one.</span></p>
  </div>
</footer>
<!--/page-->
<script>
window.PLUSONE = <?= json_encode([
    'preview' => false,
    'endpoint' => 'quote.php',
    'whatsapp' => $wa,
    'moods' => array_values($moods),
    'plus' => PLUS_D,
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
</body>
</html>
