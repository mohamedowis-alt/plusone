<?php
// The first set of +1 menus, autumn 2026.
//
// Written as a starting point for the kitchen to review: every dish, every
// description and every source tag needs a cook-through and a yes from the
// team. In the admin they show as "To review" until someone saves them.
//
// Keyed by the party's slug. Each dish is [course, name, one line, source tag].
// Source tags: grown = Locally grown, butchery = RDNA butchery,
// made = Home made, cooked = Cooked to order, '' = no tag.

return [
    'buffet' => [
        'season' => 'Autumn 2026',
        'title' => 'The long table',
        'intro' => 'Egyptian cooking for a full table. Big dishes down the middle, a card on each one that says where it came from, and bread baked while you watch.',
        'dishes' => [
            ['To start', 'Garden leaves', 'Rocket, dill and coriander picked this week, baladi lemon, olive oil. Dressed at the table.', 'grown'],
            ['To start', 'Ruby beets', 'Roast beetroot, white cheese, pomegranate and mint.', 'grown'],
            ['To start', 'Burnt aubergine', 'Charred whole over the flame, then tahini, cumin and lemon.', 'made'],
            ['To start', 'Golden lentils', 'A warm lentil soup with cumin, lemon and crisp bread.', 'made'],
            ['To start', 'Bread from the fire', 'Baladi bread, baked in front of your guests.', 'cooked'],
            ['The table', 'Lemon chicken', 'Our own chicken, roasted whole with lemon and garlic. Carved to order.', 'cooked'],
            ['The table', 'Overnight beef', 'From our butchery. Cooked low through the night with onions and bay.', 'butchery'],
            ['The table', 'Charcoal kofta', 'Minced the same day, grilled over charcoal, served on parsley and onion.', 'butchery'],
            ['The table', 'Smoked freekeh', 'Green wheat with roast pumpkin and toasted nuts. A main for guests who skip the meat.', 'grown'],
            ['The table', 'Garden torly', 'Autumn vegetables baked slowly in tomato, the Egyptian way.', 'grown'],
            ['The table', 'Golden rice', 'Egyptian rice, toasted vermicelli, ghee.', 'made'],
            ['To finish', 'Om Ali', 'Pastry, milk, nuts and raisins. Served warm from the oven.', 'made'],
            ['To finish', 'The batata cart', 'Sweet potato roasted until it caramelises, with cream and black honey.', 'grown'],
            ['To finish', 'Fruit of the week', 'Guava, pomegranate, persimmon. Whatever is best right now.', 'grown'],
            ['To drink', 'Karkade and tamarind', 'Made in our kitchen, served cold.', 'made'],
        ],
    ],

    'pizza-party' => [
        'season' => 'Autumn 2026',
        'title' => 'Hot from the oven',
        'intro' => 'Our oven comes to you. Dough made that morning, stretched and baked in front of your guests. Italian classics, with our own sausage and produce from the farm.',
        'dishes' => [
            ['To begin', 'Rosemary focaccia', 'Baked in the pizza oven, with olive oil and sea salt.', 'cooked'],
            ['To begin', 'Bruschetta', 'Tomato, garlic and basil on grilled bread.', 'grown'],
            ['To begin', 'Rocket salad', 'Rocket from the farm, tomato, lemon and olive oil.', 'grown'],
            ['From the oven', 'Margherita', 'Tomato, mozzarella, basil.', 'cooked'],
            ['From the oven', 'Marinara', 'Tomato, garlic, oregano and olive oil. No cheese.', 'cooked'],
            ['From the oven', 'Salsiccia', 'Our own sausage, roast peppers, onion.', 'butchery'],
            ['From the oven', 'Ortolana', 'Aubergine, courgette and peppers from the farm, grilled first.', 'grown'],
            ['From the oven', 'Bianca', 'Mozzarella, garlic, rocket and lemon. No tomato.', 'cooked'],
            ['From the oven', 'Basterma and rocket', 'Our answer to bresaola: cured beef, mozzarella, and rocket after the oven.', 'cooked'],
            ['To finish', 'Tiramisu', 'Made in our kitchen the day before, the way it should be.', 'made'],
            ['To finish', 'The last pizza', 'Cream and honey on hot dough, torn and shared.', 'cooked'],
            ['To drink', 'Lemonade with mint', 'Lemons, mint, a little sugar.', 'made'],
        ],
    ],

    'barbecue' => [
        'season' => 'Autumn 2026',
        'title' => 'Around the fire',
        'intro' => 'A charcoal grill, a grill master and meat from our own butchery. Everyone ends up standing around it.',
        'dishes' => [
            ['From the fire', 'Charcoal kofta', 'Minced the same day with parsley and onion.', 'butchery'],
            ['From the fire', 'Lamb chops', 'Salt, pepper, fire. Nothing else.', 'butchery'],
            ['From the fire', "The butcher's cut", 'One large cut chosen by our butchery that week, grilled whole and sliced at the grill.', 'butchery'],
            ['From the fire', 'Chicken shish', 'Our own chicken, after a night in yoghurt, lemon and garlic.', 'cooked'],
            ['From the fire', 'Blistered vegetables', 'Aubergine, peppers, onion and tomato, charred and dressed with lemon.', 'grown'],
            ['From the fire', 'Batata in the embers', 'Sweet potato cooked in the coals, split open with butter and salt.', 'grown'],
            ['On the table', 'Bread from the fire', 'Baladi bread, baked in front of your guests.', 'cooked'],
            ['On the table', 'Three bowls', 'Tahini, burnt aubergine, and a sharp tomato and garlic salsa.', 'made'],
            ['On the table', 'Baladi salad', 'Tomato, cucumber, onion and herbs, chopped small, with lemon.', 'grown'],
            ['On the table', 'Golden rice', 'Egyptian rice, toasted vermicelli, ghee.', 'made'],
            ['On the table', 'House pickles', 'Carrot, turnip, lemon and chilli, pickled in our kitchen.', 'made'],
            ['To finish', 'Zalabia, hot', 'Small doughnuts fried to order, with honey or sugar.', 'cooked'],
            ['To finish', 'Fruit of the week', 'Guava, pomegranate, persimmon.', 'grown'],
            ['To drink', 'Karkade and tamarind', 'Made in our kitchen, served cold.', 'made'],
        ],
    ],

    'coffee-break' => [
        'season' => 'Autumn and winter 2026',
        'title' => 'The good break',
        'intro' => 'Food people leave their desks for. Set up before the meeting, cleared before the next one.',
        'dishes' => [
            ['To drink', 'Coffee and tea', 'Brewed on the spot.', 'cooked'],
            ['To drink', 'Juice of the day', 'Pressed that morning.', 'grown'],
            ['To drink', 'Real hot chocolate', 'Chocolate melted into milk. No powder.', 'made'],
            ['Savoury', 'Eggs your way', 'For morning meetings. Cooked to order, with herbs or cheese.', 'cooked'],
            ['Savoury', 'Butter croissants', 'Plain or with cheese. Baked that morning.', 'made'],
            ['Savoury', 'Small sandwiches', 'On our own bread: roast chicken, egg and herbs, cheese and tomato.', 'made'],
            ['Savoury', 'Quiche of the week', 'Vegetables from the farm in a butter crust.', 'grown'],
            ['Sweet', 'Cookies, still warm', 'Chocolate chunk, and oat and raisin. Baked that morning and served warm.', 'made'],
            ['Sweet', 'Sticky date cake', 'A dark, soft loaf, sliced thick.', 'made'],
            ['Sweet', 'Lemon loaf', 'Sharp with lemon, with a thin glaze.', 'made'],
            ['Sweet', 'Morning pots', 'Yoghurt, our own granola and fruit.', 'made'],
            ['Sweet', 'Fruit cups', 'Cut to order.', 'grown'],
        ],
    ],

    'birthday' => [
        'season' => 'Autumn 2026',
        'title' => 'Make a wish',
        'intro' => 'Food children finish and parents steal. A table for the grown-ups too, and a cake made for the day.',
        'dishes' => [
            ['Small hands', 'Mini burgers', 'Beef from our butchery, soft buns baked in our kitchen.', 'butchery'],
            ['Small hands', 'Crispy chicken', 'Our own chicken, crumbed by hand.', 'made'],
            ['Small hands', 'Pizza squares', 'Tomato and cheese, straight from the oven.', 'cooked'],
            ['Small hands', 'Pasta to order', 'Tomato or white sauce, cooked in the pan while they watch.', 'cooked'],
            ['Small hands', 'Rainbow sticks', 'Carrot, cucumber and peppers with tahini and labneh.', 'grown'],
            ['Small hands', 'Fruit sticks', 'The colourful plate that empties first.', 'grown'],
            ['For the grown-ups', 'Garden leaves', 'Rocket, herbs, lemon.', 'grown'],
            ['For the grown-ups', 'Kofta rolls', 'Charcoal kofta in warm bread with tahini and pickles.', 'butchery'],
            ['For the grown-ups', 'Burnt aubergine', 'With tahini and warm bread.', 'made'],
            ['The moment', 'The birthday cake', 'Made to order. Tell us the name and the number.', 'made'],
            ['The moment', 'Fruit ice pops', 'Guava, pomegranate or karkade. Fruit, water, a little sugar.', 'made'],
            ['The moment', 'Lemonade with mint', 'Lemons, mint, a little sugar.', 'made'],
        ],
    ],

    'iftar' => [
        'season' => 'Ramadan 2027',
        'title' => 'When the sun sets',
        'intro' => 'On the table before the call to prayer and served to share, the way an iftar should be.',
        'dishes' => [
            ['To break the fast', 'Dates and milk', 'Waiting at every place.', 'grown'],
            ['To break the fast', 'Three Ramadan drinks', 'Qamar el-din, karkade and tamarind, made in our kitchen.', 'made'],
            ['To break the fast', 'Golden lentils', 'A warm lentil soup with cumin, lemon and crisp bread.', 'made'],
            ['To break the fast', 'Sambousek', 'Cheese and meat, folded by hand, fried just before sunset.', 'made'],
            ['To break the fast', 'Garden leaves', 'Rocket, dill and coriander, baladi lemon, olive oil.', 'grown'],
            ['The table', 'Lamb fattah', 'Lamb from our butchery, rice, crisp bread, garlic and vinegar.', 'butchery'],
            ['The table', 'Chicken with freekeh', 'Our own chicken, roasted and filled with smoked green wheat.', 'cooked'],
            ['The table', 'Winter tagine', 'Green peas, carrots and beef in tomato, baked in clay.', 'butchery'],
            ['The table', 'Mahshi', 'Vine leaves and winter cabbage, rolled by hand.', 'made'],
            ['The table', 'Golden rice', 'Egyptian rice, toasted vermicelli, ghee.', 'made'],
            ['The table', 'Bread from the fire', 'Baladi bread, baked in front of your guests.', 'cooked'],
            ['To finish', 'Qatayef to order', 'Filled with nuts or cream, fried in front of you, dipped in syrup.', 'cooked'],
            ['To finish', 'Konafa', 'With cream, still warm.', 'made'],
            ['To finish', 'Winter fruit', 'Oranges and strawberries, at their best at this time of year.', 'grown'],
            ['To finish', 'Tea with fresh mint', 'And coffee, for the long evening.', ''],
        ],
    ],
];
