<?php
declare(strict_types=1);

require __DIR__ . '/_layout.php';
require_login();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plus-one-requests-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // so Excel reads accents and Arabic correctly
$cols = [
    'Reference', 'Sent', 'Status', 'Name', 'Company', 'Mobile', 'Email', 'Reply by', 'Setting', 'Occasion', 'Guests',
    'Party', 'Service', 'Event date', 'Time of day', 'Area', 'Indoors or out', 'Vibe', 'Live cooking', 'Dietary needs',
    'Budget', 'Notes', 'Team notes', 'Language',
];
fputcsv($out, $cols);

// Cells that start with = + - @ can run as formulas in a spreadsheet, so they get a leading quote.
$safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;

foreach (rows('SELECT * FROM requests ORDER BY id DESC') as $r) {
    fputcsv($out, array_map($safe, [
        request_ref((int) $r['id']), $r['created_at'], STATUSES[$r['status']] ?? $r['status'], $r['name'], $r['company'],
        $r['phone'], $r['email'], $r['contact_pref'], SETTINGS_LABELS[$r['setting']] ?? '', $r['event_type'], $r['guests'],
        $r['parties'], STYLES[$r['style']][0] ?? '', $r['event_date'], $r['time_of_day'], $r['area'], $r['venue'],
        $r['vibe'], LIVE_COOKING[$r['live_cooking']] ?? '', $r['dietary'], $r['budget'], $r['notes'], $r['admin_note'],
        ($r['lang'] ?? 'en') === 'ar' ? 'Arabic' : 'English',
    ]));
}
fclose($out);
