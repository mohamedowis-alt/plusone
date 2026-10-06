<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
$user = require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'test') {
        $to = mail_list((string) setting('notify_email', ''));
        $sent = send_mail(
            $to,
            'Test email from the +1 website',
            mail_layout('It works', '<p style="margin:0;font-size:16px;line-height:1.5;">This is a test from the +1 website. Quote requests will arrive at this address.</p>'),
            "It works.\n\nThis is a test from the +1 website. Quote requests will arrive at this address.\n"
        );
        flash($sent['ok'] ? 'Test email sent to ' . implode(', ', $to) . '. Check the inbox, and the spam folder.' : 'The test did not go: ' . $sent['error'], $sent['ok'] ? 'ok' : 'bad');
        redirect('settings.php');
    }

    if ($action === 'save') {
        $notify = mail_list(post('notify_email', 600));
        $from = strtolower(post('from_email', 190));
        $method = post('mail_method') === 'smtp' ? 'smtp' : 'mail';
        $secure = in_array(post('smtp_secure'), ['tls', 'ssl', 'none'], true) ? post('smtp_secure') : 'tls';
        $siteUrl = post('site_url', 200);

        if (!$notify) {
            $errors[] = 'Enter at least one valid address to receive quote requests.';
        }
        if (!mail_valid($from)) {
            $errors[] = 'The "send from" address is not valid.';
        }
        if ($method === 'smtp' && post('smtp_host', 190) === '') {
            $errors[] = 'Enter the SMTP server, or choose the web server option.';
        }
        if ($siteUrl !== '' && !preg_match('#^https?://[^\s]+$#i', $siteUrl)) {
            $errors[] = 'The site address must start with https://';
        }

        if (!$errors) {
            set_setting('notify_email', implode(', ', $notify));
            set_setting('confirm_guest', isset($_POST['confirm_guest']) ? '1' : '0');
            set_setting('from_email', $from);
            set_setting('from_name', post('from_name', 80) ?: '+1 by RDNA');
            set_setting('mail_method', $method);
            set_setting('smtp_host', post('smtp_host', 190));
            set_setting('smtp_port', (string) max(1, min(65535, (int) post('smtp_port') ?: 587)));
            set_setting('smtp_secure', $secure);
            set_setting('smtp_user', post('smtp_user', 190));
            $pass = (string) ($_POST['smtp_pass'] ?? '');
            if ($pass !== '') {
                set_setting('smtp_pass', $pass);
            }
            set_setting('whatsapp', wa_number(post('whatsapp', 40)));
            set_setting('instagram', ltrim(post('instagram', 60), '@'));
            set_setting('site_url', $siteUrl);
            flash('Settings saved.');
            redirect('settings.php');
        }
    }
}

$v = fn (string $k, string $d = '') => $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$k]) && is_string($_POST[$k]) ? $_POST[$k] : (string) setting($k, $d);
$method = $v('mail_method', 'mail');

admin_head('Settings', 'settings');
?>
<div class="page-head">
  <div>
    <h1>Settings</h1>
    <p>Where quote requests go, how the site sends email, and the contact details shown to guests.</p>
  </div>
</div>

<?php foreach ($errors as $err): ?><p class="note bad" role="alert"><?= e($err) ?></p><?php endforeach; ?>

<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">

  <div class="card">
    <h2>Quote requests</h2>
    <div class="grid2">
      <label class="full">Send every request to <span class="hint">One address, or several separated by commas. Change it any time.</span>
        <input name="notify_email" value="<?= e($v('notify_email')) ?>" placeholder="events@yourdomain.com" required>
      </label>
      <label class="tick full"><input type="checkbox" name="confirm_guest" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['confirm_guest']) : setting('confirm_guest', '1') === '1') ? 'checked' : '' ?>> Also email the guest a short "we have your request" note, when they give an email address</label>
    </div>
  </div>

  <div class="card">
    <h2>How the site sends email</h2>
    <div class="grid2">
      <label>Send from <span class="hint">An address on your own domain.</span><input type="email" name="from_email" value="<?= e($v('from_email')) ?>" required></label>
      <label>Sender name <input name="from_name" value="<?= e($v('from_name', '+1 by RDNA')) ?>" maxlength="80"></label>
    </div>
    <div class="choice" style="margin-top:18px">
      <label><input type="radio" name="mail_method" value="smtp" <?= $method === 'smtp' ? 'checked' : '' ?>> Through a mailbox (SMTP) <span class="hint">Recommended. Emails sent this way reach the inbox. Your email provider gives you these details.</span></label>
      <label><input type="radio" name="mail_method" value="mail" <?= $method !== 'smtp' ? 'checked' : '' ?>> Through the web server <span class="hint">Nothing to fill in, but on many hosts these emails land in spam or do not arrive.</span></label>
    </div>
    <div class="grid3" id="smtp-fields">
      <label>SMTP server <input name="smtp_host" value="<?= e($v('smtp_host')) ?>" placeholder="smtp.yourprovider.com"></label>
      <label>Port <input type="number" name="smtp_port" value="<?= e($v('smtp_port', '587')) ?>" min="1" max="65535"></label>
      <label>Security
        <select name="smtp_secure">
          <option value="tls" <?= $v('smtp_secure', 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (port 587)</option>
          <option value="ssl" <?= $v('smtp_secure', 'tls') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)</option>
          <option value="none" <?= $v('smtp_secure', 'tls') === 'none' ? 'selected' : '' ?>>None</option>
        </select>
      </label>
      <label>User name <input name="smtp_user" value="<?= e($v('smtp_user')) ?>" autocomplete="off"></label>
      <label>Password <span class="hint"><?= setting('smtp_pass', '') !== '' ? 'Saved. Leave empty to keep it.' : 'Not set yet.' ?></span><input type="password" name="smtp_pass" value="" autocomplete="new-password"></label>
    </div>
  </div>

  <div class="card">
    <h2>Shown to guests</h2>
    <div class="grid3">
      <label>WhatsApp number <input name="whatsapp" value="<?= e(phone_display($v('whatsapp'))) ?>" inputmode="tel"></label>
      <label>Instagram handle <input name="instagram" value="<?= e($v('instagram')) ?>" placeholder="plusonerdna"></label>
      <label>Site address <span class="hint">Used for links in emails. Leave empty to detect it.</span><input name="site_url" value="<?= e($v('site_url')) ?>" placeholder="https://"></label>
    </div>
  </div>

  <div class="actions">
    <button class="btn" type="submit">Save settings</button>
  </div>
</form>

<form class="card" method="post" style="margin-top:28px">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="test">
  <h2>Check it works</h2>
  <p class="muted">Save first, then send a test to the request address above.</p>
  <div class="actions"><button class="btn ghost" type="submit">Send a test email</button></div>
</form>

<script>
(function () {
  var box = document.getElementById('smtp-fields');
  function sync() { box.hidden = document.querySelector('input[name=mail_method]:checked').value !== 'smtp'; }
  document.querySelectorAll('input[name=mail_method]').forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
<?php admin_foot();
