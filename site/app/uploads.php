<?php
declare(strict_types=1);

// Menu pictures and PDFs uploaded by the team.

const UPLOAD_DIR = APP_ROOT . '/uploads/menus';
const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;

/** The largest upload this server accepts, as text for the form. */
function upload_limit_text(): string
{
    $toBytes = function (string $v): int {
        $v = trim($v);
        $n = (int) $v;
        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n,
        };
    };
    $limit = min(UPLOAD_MAX_BYTES, $toBytes((string) ini_get('upload_max_filesize')) ?: UPLOAD_MAX_BYTES,
        $toBytes((string) ini_get('post_max_size')) ?: UPLOAD_MAX_BYTES);
    return max(1, (int) floor($limit / 1024 / 1024)) . ' MB';
}

/**
 * Save an uploaded picture or PDF. $kind is 'image' or 'pdf'.
 * Returns ['file' => saved name or '', 'error' => message or ''].
 * An empty file field is not an error.
 */
function save_upload(string $field, string $kind): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || !is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['file' => '', 'error' => ''];
    }
    if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'] > UPLOAD_MAX_BYTES) {
        return ['file' => '', 'error' => 'That file is too big. The limit is ' . upload_limit_text() . '.'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return ['file' => '', 'error' => 'The upload did not arrive. Try again.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
    $allowed = $kind === 'pdf'
        ? ['application/pdf' => 'pdf']
        : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return ['file' => '', 'error' => $kind === 'pdf'
            ? 'The menu file must be a PDF.'
            : 'The picture must be a JPG, PNG or WebP.'];
    }
    $ext = $allowed[$mime];
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;

    if ($kind === 'image' && function_exists('imagecreatefromstring')) {
        // Re-save pictures at a sensible size. This also drops hidden data such as location.
        $src = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
        if (!$src) {
            return ['file' => '', 'error' => 'That picture could not be read. Try saving it again as a JPG.'];
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, 1600 / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 253, 232));
        imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $name = preg_replace('/\.\w+$/', '.jpg', $name);
        $dest = UPLOAD_DIR . '/' . $name;
        $ok = imagejpeg($out, $dest, 84);
        imagedestroy($src);
        imagedestroy($out);
        if (!$ok) {
            return ['file' => '', 'error' => 'The picture could not be saved. Check that uploads/menus is writable.'];
        }
        return ['file' => $name, 'error' => ''];
    }

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        return ['file' => '', 'error' => 'The file could not be saved. Check that uploads/menus is writable.'];
    }
    return ['file' => $name, 'error' => ''];
}

function delete_upload(string $name): void
{
    $name = basename($name);
    if ($name !== '' && is_file(UPLOAD_DIR . '/' . $name)) {
        @unlink(UPLOAD_DIR . '/' . $name);
    }
}

/** Does this text look like it carries a price? The site shows menus without prices. */
function looks_like_price(string $text): bool
{
    return (bool) preg_match('/(EGP|L\.?E\.?|USD|\$|€|£|جنيه|ج\.م)\s*\d|\d[\d,.]*\s*(EGP|L\.?E\.?\b|USD|pounds|جنيه|ج\.م)/iu', $text);
}
