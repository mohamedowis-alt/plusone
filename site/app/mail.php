<?php
declare(strict_types=1);

// Sending email. Two ways, chosen in Admin > Settings:
//   - "smtp": through a mailbox you own (recommended, far more reliable)
//   - "mail": through the web server's own mail function
// No outside library is needed.

function mail_clean_header(string $s): string
{
    return trim(preg_replace('/[\r\n\t]+/', ' ', $s) ?? '');
}

function mail_encode_name(string $name): string
{
    $name = mail_clean_header($name);
    if ($name === '') {
        return '';
    }
    return preg_match('/^[A-Za-z0-9 .\-]+$/', $name) ? '"' . $name . '"' : '=?UTF-8?B?' . base64_encode($name) . '?=';
}

function mail_valid(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** "a@x.com, b@y.com" -> ['a@x.com', 'b@y.com'], valid addresses only. */
function mail_list(string $raw): array
{
    $out = [];
    foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $a) {
        if ($a !== '' && mail_valid($a) && !in_array(strtolower($a), $out, true)) {
            $out[] = strtolower($a);
        }
    }
    return $out;
}

/**
 * Send one email to one or more people.
 * Returns ['ok' => bool, 'error' => string].
 */
function send_mail(array $to, string $subject, string $html, string $text, string $replyTo = ''): array
{
    $to = array_values(array_filter($to, 'mail_valid'));
    if (!$to) {
        return ['ok' => false, 'error' => 'No valid address to send to.'];
    }
    $from = trim((string) setting('from_email', ''));
    if (!mail_valid($from)) {
        return ['ok' => false, 'error' => 'The sender address in Settings is missing or not valid.'];
    }
    $fromName = (string) setting('from_name', '+1 by RDNA');
    $subject = mail_clean_header($subject);
    $boundary = 'p1-' . bin2hex(random_bytes(12));
    $domain = substr(strrchr($from, '@') ?: '@localhost', 1);

    $headers = [
        'From' => trim(mail_encode_name($fromName) . ' <' . $from . '>'),
        'Date' => date('r'),
        'Message-ID' => '<' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version' => '1.0',
        'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
    ];
    if ($replyTo !== '' && mail_valid($replyTo)) {
        $headers['Reply-To'] = $replyTo;
    }
    $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text)) . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "--$boundary--\r\n";
    $encSubject = preg_match('/^[\x20-\x7E]*$/', $subject) ? $subject : '=?UTF-8?B?' . base64_encode($subject) . '?=';

    try {
        if (setting('mail_method', 'mail') === 'smtp') {
            $headers = ['To' => implode(', ', $to), 'Subject' => $encSubject] + $headers;
            $lines = '';
            foreach ($headers as $k => $v) {
                $lines .= $k . ': ' . $v . "\r\n";
            }
            smtp_send($from, $to, $lines . "\r\n" . $body);
        } else {
            $lines = '';
            foreach ($headers as $k => $v) {
                $lines .= $k . ': ' . $v . "\r\n";
            }
            $ok = @mail(implode(', ', $to), $encSubject, $body, $lines, '-f' . $from);
            if (!$ok) {
                $ok = @mail(implode(', ', $to), $encSubject, $body, $lines);
            }
            if (!$ok) {
                throw new RuntimeException('The web server refused to send the email. Switch to SMTP in Settings.');
            }
        }
    } catch (Throwable $e) {
        error_log('+1 mail error: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    return ['ok' => true, 'error' => ''];
}

/** A small SMTP client: SSL or STARTTLS, with login. */
function smtp_send(string $from, array $to, string $message): void
{
    $host = trim((string) setting('smtp_host', ''));
    $port = (int) setting('smtp_port', '587');
    $secure = (string) setting('smtp_secure', 'tls');
    $user = (string) setting('smtp_user', '');
    $pass = (string) setting('smtp_pass', '');
    if ($host === '') {
        throw new RuntimeException('The SMTP server in Settings is empty.');
    }
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        throw new RuntimeException('Could not reach the mail server ' . $host . ':' . $port . ' (' . $errstr . ').');
    }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $line, array $expect, string $label = '') use ($fp, $read): string {
        fwrite($fp, $line . "\r\n");
        $reply = $read();
        if (!in_array((int) substr($reply, 0, 3), $expect, true)) {
            throw new RuntimeException('Mail server said no at ' . ($label ?: strtok($line, ' ')) . ': ' . trim($reply));
        }
        return $reply;
    };

    try {
        $greet = $read();
        if ((int) substr($greet, 0, 3) !== 220) {
            throw new RuntimeException('Mail server did not answer properly: ' . trim($greet));
        }
        $me = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? '')) ?: 'localhost';
        $cmd('EHLO ' . $me, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not start a secure connection with the mail server.');
            }
            $cmd('EHLO ' . $me, [250]);
        }
        if ($user !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode($user), [334], 'user name');
            $cmd(base64_encode($pass), [235], 'password');
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        foreach ($to as $rcpt) {
            $cmd('RCPT TO:<' . $rcpt . '>', [250, 251]);
        }
        $cmd('DATA', [354]);
        // Lines that start with a dot must be doubled.
        $message = preg_replace('/^\./m', '..', $message) ?? $message;
        $cmd(rtrim($message, "\r\n") . "\r\n.", [250], 'message');
        fwrite($fp, "QUIT\r\n");
    } finally {
        fclose($fp);
    }
}

// ---------------------------------------------------------------- the emails

function mail_layout(string $title, string $inner): string
{
    return '<!doctype html><html><body style="margin:0;padding:0;background:#FBF7C6;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF7C6;"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;font-family:Helvetica,Arial,sans-serif;color:#000000;">'
        . '<tr><td style="background:#F6B11A;padding:22px 28px;font-size:13px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">+1 by RDNA</td></tr>'
        . '<tr><td style="background:#FFFDE8;padding:28px;"><h1 style="margin:0 0 18px;font-size:24px;line-height:1.2;">' . e($title) . '</h1>'
        . $inner . '</td></tr>'
        . '<tr><td style="background:#000000;color:#FBF7C6;padding:16px 28px;font-size:13px;">Our ingredients. Your event.</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Email the team about a new quote request. */
function mail_request_to_team(array $r): array
{
    $to = mail_list((string) setting('notify_email', ''));
    if (!$to) {
        return ['ok' => false, 'error' => 'No notification address is set in Settings.'];
    }
    $ref = request_ref((int) $r['id']);
    $rowsHtml = '';
    $text = "New request $ref\n" . request_headline($r) . "\n\n";
    foreach (request_rows($r) as $label => $value) {
        $rowsHtml .= '<tr><td style="padding:8px 12px 8px 0;border-top:1px solid #E5DFA6;font-size:13px;color:#55513C;white-space:nowrap;vertical-align:top;">'
            . e($label) . '</td><td style="padding:8px 0;border-top:1px solid #E5DFA6;font-size:15px;vertical-align:top;">'
            . nl2br(e($value)) . '</td></tr>';
        $text .= $label . ': ' . $value . "\n";
    }
    $admin = site_url() . 'admin/request.php?id=' . (int) $r['id'];
    $wa = 'https://wa.me/' . wa_number($r['phone']);
    $btn = 'display:inline-block;padding:12px 20px;border-radius:24px;font-weight:bold;font-size:14px;text-decoration:none;';
    $inner = '<p style="margin:0 0 18px;font-size:14px;color:#55513C;">Request ' . e($ref) . ', sent ' . e(fmt_datetime($r['created_at'])) . '</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rowsHtml . '</table>'
        . '<p style="margin:24px 0 0;"><a href="' . e($wa) . '" style="' . $btn . 'background:#000000;color:#FBF7C6;">Reply on WhatsApp</a> &nbsp; '
        . '<a href="' . e($admin) . '" style="' . $btn . 'background:#F6B11A;color:#000000;">Open in admin</a></p>';
    $text .= "\nReply on WhatsApp: $wa\nOpen in admin: $admin\n";
    return send_mail($to, 'New request: ' . request_headline($r) . ' (' . $r['name'] . ')',
        mail_layout(request_headline($r), $inner), $text, $r['email']);
}

/** A short note to the guest that the request arrived. Optional, see Settings. */
function mail_request_to_guest(array $r): array
{
    if (!mail_valid($r['email'])) {
        return ['ok' => false, 'error' => 'No guest email.'];
    }
    $ref = request_ref((int) $r['id']);
    $first = trim((string) strtok($r['name'], ' '));
    $wa = 'https://wa.me/' . wa_number((string) setting('whatsapp', ''));
    $inner = '<p style="margin:0 0 14px;font-size:16px;line-height:1.5;">Hello ' . e($first) . ',</p>'
        . '<p style="margin:0 0 14px;font-size:16px;line-height:1.5;">Your request is with us: ' . e(request_headline($r)) . '. '
        . 'We will come back to you with a menu made for your gathering, and where every dish comes from.</p>'
        . '<p style="margin:0 0 14px;font-size:16px;line-height:1.5;">Your reference is <strong>' . e($ref) . '</strong>. '
        . 'To add anything, message us on <a href="' . e($wa) . '" style="color:#000000;font-weight:bold;">WhatsApp</a>.</p>';
    $text = "Hello $first,\n\nYour request is with us: " . request_headline($r) . ".\n"
        . "We will come back to you with a menu made for your gathering, and where every dish comes from.\n\n"
        . "Your reference is $ref. To add anything, message us on WhatsApp: $wa\n\n+1 by RDNA\nOur ingredients. Your event.\n";
    return send_mail([$r['email']], 'We have your request (' . $ref . ')', mail_layout('We have your request', $inner), $text);
}
