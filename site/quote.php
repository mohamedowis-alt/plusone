<?php
declare(strict_types=1);

// Receives a quote request from the site, saves it and emails the team.

require __DIR__ . '/app/bootstrap.php';
if (!installed()) {
    redirect('install.php');
}

$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

function quote_reply(bool $ok, array $data, bool $json): void
{
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        http_response_code($ok ? 200 : 422);
        echo json_encode(['ok' => $ok] + $data);
        exit;
    }
    // Back to the page in the language the request was written in.
    $back = 'index.php?' . (lang() === 'ar' ? 'lang=ar&' : '');
    if ($ok) {
        redirect($back . 'sent=' . rawurlencode($data['ref']) . '#quote');
    }
    redirect($back . 'problem=' . rawurlencode($data['error']) . '#quote');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php' . (lang() === 'ar' ? '?lang=ar' : '') . '#quote');
}

// Robots: a hidden field people never fill in, and a signed time stamp.
if (post('website') !== '') {
    quote_reply(true, ['ref' => 'P1-0000', 'whatsapp' => ''], $wantsJson);
}
if (!form_stamp_ok(post('stamp', 120))) {
    quote_reply(false, ['error' => t('This page has been open for a while. Reload it and send again.')], $wantsJson);
}
$hourAgo = date('Y-m-d H:i:s', time() - 3600);
if ((int) val('SELECT COUNT(*) FROM requests WHERE ip = ? AND created_at > ?', [client_ip(), $hourAgo]) >= 6) {
    quote_reply(false, ['error' => t('That is a lot of requests in one hour. Message us on WhatsApp instead.')], $wantsJson);
}

$setting = post('setting') === 'work' ? 'work' : 'home';
$eventType = post('event_type', 80);
if (!in_array($eventType, EVENT_TYPES[$setting], true)) {
    $eventType = '';
}
$guests = (int) post('guests', 6);
$style = post('style');
$style = isset(STYLES[$style]) ? $style : 'unsure';

$sectionNames = array_column(rows('SELECT name FROM sections WHERE is_visible = 1 ORDER BY sort_order, id'), 'name');
$parties = post_list('parties', $sectionNames);

$date = post('event_date', 10);
if ($date !== '') {
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date || $d < new DateTime('today')) {
        $date = '';
        $badDate = true;
    }
}
$time = post('time_of_day');
$time = in_array($time, TIMES_OF_DAY, true) ? $time : '';
$venue = post('venue');
$venue = in_array($venue, VENUES, true) ? $venue : '';
$vibe = post('vibe');
$vibe = in_array($vibe, VIBES, true) ? $vibe : '';
$live = post('live_cooking');
$live = isset(LIVE_COOKING[$live]) ? $live : 'advise';
$pref = post('contact_pref');
$pref = in_array($pref, CONTACT_PREFS, true) ? $pref : 'WhatsApp';

$name = post('name', 120);
$phone = post('phone', 40);
$email = strtolower(post('email', 190));

$problems = [];
if ($eventType === '') {
    $problems[] = t('Tell us what brings everyone together.');
}
if ($guests < 1 || $guests > 5000) {
    $problems[] = t('Tell us roughly how many guests.');
}
if (!empty($badDate)) {
    $problems[] = t('Choose a date from today onwards, or leave it open.');
}
if ($name === '') {
    $problems[] = t('Tell us your name.');
}
if (strlen(preg_replace('/\D+/', '', $phone) ?? '') < 8) {
    $problems[] = t('Give us a mobile number we can reach you on.');
}
if ($email !== '' && !mail_valid($email)) {
    $problems[] = t('That email address does not look right.');
}
if ($problems) {
    quote_reply(false, ['error' => implode(' ', $problems)], $wantsJson);
}

$r = [
    'created_at'   => now(),
    'status'       => 'new',
    'name'         => $name,
    'phone'        => $phone,
    'email'        => $email,
    'company'      => $setting === 'work' ? post('company', 160) : '',
    'contact_pref' => $pref,
    'setting'      => $setting,
    'event_type'   => $eventType,
    'guests'       => $guests,
    'style'        => $style,
    'parties'      => implode(', ', $parties),
    'event_date'   => $date,
    'time_of_day'  => $time,
    'area'         => post('area', 160),
    'venue'        => $venue,
    'vibe'         => $vibe,
    'live_cooking' => $live,
    'dietary'      => implode(', ', post_list('dietary', DIETARY)),
    'budget'       => post('budget', 160),
    'notes'        => post('notes', 2000),
    'admin_note'   => '',
    'ip'           => client_ip(),
    'email_sent'   => 0,
];
// The language the guest wrote in, so the team knows how to reply.
if (arabic_ready()) {
    $r['lang'] = lang();
}
$r['id'] = insert('requests', $r);

// The request is safe in the database whatever happens to the email.
$sent = mail_request_to_team($r);
if ($sent['ok']) {
    q('UPDATE requests SET email_sent = 1 WHERE id = ?', [$r['id']]);
}
if ($email !== '' && setting('confirm_guest', '1') === '1') {
    mail_request_to_guest($r);
}

$ref = request_ref($r['id']);
$wa = wa_number((string) setting('whatsapp', ''));
$message = lang() === 'ar'
    ? sprintf(t('Hello +1, I just sent a request on your website (%s).'), $ref)
    : 'Hello +1, I just sent a request on your website (' . $ref . '): ' . request_headline($r) . '.';
quote_reply(true, [
    'ref' => $ref,
    'whatsapp' => $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($message) : '',
], $wantsJson);
