<?php
declare(strict_types=1);

// Database tables and the starting content. Used once, by install.php.

function create_schema(PDO $pdo, string $driver): void
{
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $end = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id $pk,
            email VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            pass_hash VARCHAR(255) NOT NULL,
            created_at VARCHAR(19) NOT NULL
        )$end",
        "CREATE TABLE IF NOT EXISTS settings (
            k VARCHAR(64) NOT NULL PRIMARY KEY,
            v TEXT NOT NULL
        )$end",
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id $pk,
            ip VARCHAR(64) NOT NULL,
            attempted_at VARCHAR(19) NOT NULL
        )$end",
        "CREATE TABLE IF NOT EXISTS sections (
            id $pk,
            slug VARCHAR(80) NOT NULL UNIQUE,
            name VARCHAR(80) NOT NULL,
            sum_a VARCHAR(40) NOT NULL,
            sum_b VARCHAR(40) NOT NULL,
            best_for VARCHAR(160) NOT NULL,
            mark VARCHAR(20) NOT NULL,
            colour VARCHAR(20) NOT NULL,
            sort_order INT NOT NULL,
            is_visible INT NOT NULL
        )$end",
        "CREATE TABLE IF NOT EXISTS menus (
            id $pk,
            section_id INT NOT NULL,
            title VARCHAR(160) NOT NULL,
            season VARCHAR(80) NOT NULL,
            intro TEXT NOT NULL,
            image VARCHAR(160) NOT NULL,
            pdf VARCHAR(160) NOT NULL,
            is_published INT NOT NULL,
            is_sample INT NOT NULL,
            sort_order INT NOT NULL,
            updated_at VARCHAR(19) NOT NULL
        )$end",
        "CREATE TABLE IF NOT EXISTS dishes (
            id $pk,
            menu_id INT NOT NULL,
            course VARCHAR(80) NOT NULL,
            name VARCHAR(160) NOT NULL,
            description VARCHAR(400) NOT NULL,
            tag VARCHAR(20) NOT NULL,
            sort_order INT NOT NULL
        )$end",
        "CREATE TABLE IF NOT EXISTS requests (
            id $pk,
            created_at VARCHAR(19) NOT NULL,
            status VARCHAR(20) NOT NULL,
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            email VARCHAR(190) NOT NULL,
            company VARCHAR(160) NOT NULL,
            contact_pref VARCHAR(40) NOT NULL,
            setting VARCHAR(10) NOT NULL,
            event_type VARCHAR(80) NOT NULL,
            guests INT NOT NULL,
            style VARCHAR(20) NOT NULL,
            parties VARCHAR(400) NOT NULL,
            event_date VARCHAR(10) NOT NULL,
            time_of_day VARCHAR(40) NOT NULL,
            area VARCHAR(160) NOT NULL,
            venue VARCHAR(40) NOT NULL,
            vibe VARCHAR(80) NOT NULL,
            live_cooking VARCHAR(20) NOT NULL,
            dietary VARCHAR(400) NOT NULL,
            budget VARCHAR(160) NOT NULL,
            notes TEXT NOT NULL,
            admin_note TEXT NOT NULL,
            ip VARCHAR(64) NOT NULL,
            email_sent INT NOT NULL
        )$end",
    ];
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
}

/** Starting settings, the six parties and the first menu for each. */
function seed_content(PDO $pdo, string $notifyEmail, string $fromEmail): void
{
    $put = function (string $table, array $data) use ($pdo): int {
        $cols = array_keys($data);
        $st = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES ('
            . implode(', ', array_fill(0, count($cols), '?')) . ')');
        $st->execute(array_values($data));
        return (int) $pdo->lastInsertId();
    };

    $settings = [
        'notify_email'  => $notifyEmail,
        'from_email'    => $fromEmail,
        'from_name'     => '+1 by RDNA',
        'mail_method'   => 'mail',
        'smtp_host'     => '',
        'smtp_port'     => '587',
        'smtp_secure'   => 'tls',
        'smtp_user'     => '',
        'smtp_pass'     => '',
        'confirm_guest' => '1',
        'whatsapp'      => '201016649967',
        'instagram'     => 'plusonerdna',
        'site_url'      => '',
        'schema_version' => (string) SCHEMA_VERSION,
    ];
    foreach ($settings as $k => $v) {
        $put('settings', ['k' => $k, 'v' => $v]);
    }

    // name, sum, best for, mark, colour
    $parties = [
        ['Buffet', 'One table', 'everyone', 'Family gatherings, company days', 'cloche', 'green'],
        ['Pizza party', 'Pizza', 'people', 'Birthdays, casual evenings', 'pizza', 'yellow'],
        ['Barbecue', 'Fire', 'friends', 'Gardens, rooftops, Sahel', 'flame', 'red'],
        ['Coffee break', 'Coffee', 'break', 'Meetings and trainings', 'steam', 'purple'],
        ['Birthday', 'Cake', 'candles', 'Children and milestones', 'hat', 'amber'],
        ['Iftar', 'Sunset', 'family', 'Home and company iftars', 'crescent', 'black'],
    ];
    $menus = require __DIR__ . '/menus.first.php';

    foreach ($parties as $i => $p) {
        [$name, $a, $b, $best, $mark, $colour] = $p;
        $slug = strtolower(str_replace(' ', '-', $name));
        $sid = $put('sections', [
            'slug' => $slug, 'name' => $name, 'sum_a' => $a, 'sum_b' => $b,
            'best_for' => $best, 'mark' => $mark, 'colour' => $colour, 'sort_order' => $i + 1, 'is_visible' => 1,
        ]);
        if (!isset($menus[$slug])) {
            continue;
        }
        $m = $menus[$slug];
        // is_sample = 1 shows the menu as "To review" in the admin until the team saves it.
        $mid = $put('menus', [
            'section_id' => $sid, 'title' => $m['title'], 'season' => $m['season'], 'intro' => $m['intro'], 'image' => '', 'pdf' => '',
            'is_published' => 1, 'is_sample' => 1, 'sort_order' => 1, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($m['dishes'] as $j => $d) {
            $put('dishes', [
                'menu_id' => $mid, 'course' => $d[0], 'name' => $d[1], 'description' => $d[2], 'tag' => $d[3],
                'sort_order' => $j + 1,
            ]);
        }
    }
}
