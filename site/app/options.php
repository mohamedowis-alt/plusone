<?php
// The choices offered in the quote request, and the labels used across the site.
// Change a label here and it changes on the site, in the admin and in the emails.

const EVENT_TYPES = [
    'home' => [
        'Birthday', 'Family gathering', 'Dinner with friends', 'Engagement',
        'Iftar or suhoor', 'Graduation', 'Something else',
    ],
    'work' => [
        'Team lunch', 'Meeting or training', 'Company day', 'Client evening',
        'Launch or opening', 'Company iftar', 'Something else',
    ],
];

const SETTINGS_LABELS = ['home' => 'At home', 'work' => 'At work'];

const STYLES = [
    'box'    => ['Box', 'Dropped at your door, ready to serve.'],
    'table'  => ['Table', 'Set up and served by our team.'],
    'hosted' => ['Hosted', 'The whole event, start to finish.'],
    'unsure' => ['Not sure yet', 'Tell us about the day and we will suggest one.'],
];

const TIMES_OF_DAY = ['Morning', 'Lunch', 'Afternoon', 'Evening'];

const VENUES = ['Indoors', 'Outdoors', 'A bit of both'];

const AREAS = [
    'New Cairo', 'Maadi', 'Zamalek', 'Heliopolis', 'Mohandessin', 'Dokki',
    'Sheikh Zayed', '6th of October', 'North Coast',
];

const VIBES = [
    'Relaxed and easy', 'Lively party', 'Sit-down and special',
    'Children everywhere', 'Smart and businesslike',
];

const LIVE_COOKING = [
    'yes'    => 'Yes, cook in front of us',
    'no'     => 'No, bring it ready',
    'advise' => 'You tell us',
];

const DIETARY = [
    'Vegetarian guests', 'Vegan guests', 'No nuts', 'No gluten', 'No dairy', 'Food for children',
];

const CONTACT_PREFS = ['WhatsApp', 'Phone call', 'Email'];

const STATUSES = [
    'new'      => 'New',
    'talking'  => 'In conversation',
    'quoted'   => 'Quote sent',
    'won'      => 'Booked',
    'lost'     => 'Not going ahead',
];

// Source tags a dish can carry. Each one is a promise, so use them truthfully.
const DISH_TAGS = [
    ''         => ['No tag', 'plain', ''],
    'grown'    => ['Locally grown', 'sprout', 'green'],
    'butchery' => ['RDNA butchery', 'slice', 'black'],
    'made'     => ['Home made', 'check', 'yellow'],
    'cooked'   => ['Cooked to order', 'steam', 'amber'],
];

// Marks a party can wear on the site.
const SECTION_MARKS = [
    'cloche'   => 'Cloche',
    'pizza'    => 'Pizza',
    'flame'    => 'Flame',
    'steam'    => 'Steam',
    'hat'      => 'Party hat',
    'crescent' => 'Crescent',
    'pumpkin'  => 'Pumpkin',
    'sprout'   => 'Sprout',
    'heart'    => 'Heart',
    'plain'    => 'Plain plus',
];

const SECTION_COLOURS = [
    'amber'  => 'Amber',
    'red'    => 'Red',
    'green'  => 'Green',
    'yellow' => 'Yellow',
    'purple' => 'Purple',
    'black'  => 'Black',
];
