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

/** Starting settings, the six parties and one sample menu for each. */
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

    // name, sum, best for, mark, colour, season, menu title, intro, dishes [course, name, description, tag]
    $parties = [
        ['Buffet', 'One table', 'everyone', 'Family gatherings, company days', 'cloche', 'green',
            'Autumn 2026', 'The autumn table', 'Big dishes in the middle, a plate for everyone, and a card on each dish that says where it came from.', [
                ['To start', 'Garden leaves', 'Picked this week and dressed at the table.', 'grown'],
                ['To start', 'Ruby beets', 'Roasted beetroot, white cheese, herbs.', 'grown'],
                ['To start', 'Baladi bread', 'Baked the same morning.', 'made'],
                ['The table', 'Lemon chicken', 'Roasted whole, carved to order.', 'cooked'],
                ['The table', 'Charcoal kofta', 'From our own butchery, grilled over charcoal.', 'butchery'],
                ['The table', 'Overnight beef', 'Cooked low, all night.', 'butchery'],
                ['The table', 'Golden rice', 'Toasted vermicelli and ghee.', 'made'],
                ['To finish', 'Rice pudding', 'Slow-cooked milk and cinnamon.', 'made'],
                ['To finish', 'Fruit of the week', 'Whatever is best right now.', 'grown'],
            ]],
        ['Pizza party', 'Pizza', 'people', 'Birthdays, casual evenings', 'pizza', 'yellow',
            'Autumn 2026', 'Hot from the oven', 'Our oven, our dough, your garden. Guests choose, we stretch and bake in front of them.', [
                ['Pizzas', 'The red one', 'Tomato, cheese, basil.', 'cooked'],
                ['Pizzas', 'The white one', 'White cheese, rocket, lemon.', 'cooked'],
                ['Pizzas', 'The butcher', 'Our own sausage, peppers, onion.', 'butchery'],
                ['Pizzas', 'The garden', 'Vegetables of the week.', 'grown'],
                ['On the side', 'Tomato and leaves', 'A big bowl to share.', 'grown'],
                ['On the side', 'Dough balls', 'With garlic butter.', 'made'],
            ]],
        ['Barbecue', 'Fire', 'friends', 'Gardens, rooftops, Sahel', 'flame', 'red',
            'Autumn 2026', 'Around the fire', 'A grill, a grill master and meat from our own butchery. Everyone ends up standing around it.', [
                ['From the grill', 'Charcoal kofta', 'Minced the same day.', 'butchery'],
                ['From the grill', 'Lamb chops', 'Salt, pepper, fire.', 'butchery'],
                ['From the grill', 'Chicken shish', 'Marinated overnight in yoghurt and lemon.', 'cooked'],
                ['From the grill', 'Blistered vegetables', 'Peppers, onions, aubergine.', 'grown'],
                ['On the table', 'Baladi bread', 'Warmed on the grill.', 'made'],
                ['On the table', 'Tahini and tomato salad', 'Made that morning.', 'made'],
                ['On the table', 'Charred corn', 'With butter and salt.', 'grown'],
            ]],
        ['Coffee break', 'Coffee', 'break', 'Meetings and trainings', 'steam', 'purple',
            'Autumn 2026', 'The good break', 'Food people leave their desks for. Set up before your meeting, cleared before the next one.', [
                ['To drink', 'Coffee and tea', 'Brewed on the spot.', 'cooked'],
                ['To drink', 'Juice of the day', 'Pressed that morning.', 'grown'],
                ['To eat', 'Feteer bites', 'With honey and white cheese.', 'made'],
                ['To eat', 'Cheese and herb pastries', 'Baked the same morning.', 'made'],
                ['To eat', 'Date and nut bites', 'Dates, nuts, nothing else.', 'made'],
                ['To eat', 'Fruit cups', 'Cut to order.', 'grown'],
            ]],
        ['Birthday', 'Cake', 'candles', 'Children and milestones', 'hat', 'amber',
            'Autumn 2026', 'Make a wish', 'Food children finish and parents steal. And a cake made for the day.', [
                ['Small hands', 'Mini burgers', 'Beef from our own butchery, soft buns.', 'butchery'],
                ['Small hands', 'Crispy chicken', 'Real chicken, crumbed by hand.', 'made'],
                ['Small hands', 'Pizza squares', 'Tomato and cheese.', 'cooked'],
                ['Small hands', 'Fruit sticks', 'The colourful plate that empties first.', 'grown'],
                ['The moment', 'The birthday cake', 'Made to order. Tell us the name and the number.', 'made'],
                ['The moment', 'Lemonade', 'Lemons, mint, a little sugar.', 'made'],
            ]],
        ['Iftar', 'Sunset', 'family', 'Home and company iftars', 'crescent', 'black',
            'Ramadan 2027', 'When the sun sets', 'On the table before the call to prayer, served to share, the way an iftar should be.', [
                ['To break the fast', 'Dates and milk', 'Waiting at every place.', 'grown'],
                ['To break the fast', 'Lentil soup', 'With lemon and toasted bread.', 'made'],
                ['To break the fast', 'Sambousek', 'Cheese and meat, folded by hand.', 'made'],
                ['The table', 'Lamb fattah', 'Our lamb, rice, crisp bread, garlic and vinegar.', 'butchery'],
                ['The table', 'Lemon chicken', 'Roasted whole, carved to order.', 'cooked'],
                ['The table', 'Stuffed vine leaves', 'Rolled by hand.', 'made'],
                ['To finish', 'Konafa', 'With cream, still warm.', 'made'],
                ['To finish', 'Karkade and qamar el-din', 'Made in our kitchen.', 'made'],
            ]],
    ];

    foreach ($parties as $i => $p) {
        [$name, $a, $b, $best, $mark, $colour, $season, $title, $intro, $dishes] = $p;
        $sid = $put('sections', [
            'slug' => strtolower(str_replace(' ', '-', $name)), 'name' => $name, 'sum_a' => $a, 'sum_b' => $b,
            'best_for' => $best, 'mark' => $mark, 'colour' => $colour, 'sort_order' => $i + 1, 'is_visible' => 1,
        ]);
        $mid = $put('menus', [
            'section_id' => $sid, 'title' => $title, 'season' => $season, 'intro' => $intro, 'image' => '', 'pdf' => '',
            'is_published' => 1, 'is_sample' => 1, 'sort_order' => 1, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($dishes as $j => $d) {
            $put('dishes', [
                'menu_id' => $mid, 'course' => $d[0], 'name' => $d[1], 'description' => $d[2], 'tag' => $d[3],
                'sort_order' => $j + 1,
            ]);
        }
    }
}
