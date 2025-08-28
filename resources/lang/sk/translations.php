<?php

return [
    'title' => 'Master Permission Guard',
    'description' => 'Manage all permissions',
    'filament_page' => [
        'label' => 'MPG',
        'description' => 'Spravujte všetky povolenia',
        'form' => [
            'fields' => [
                'guards' => 'Guard',
            ],
        ],
        'table' => [
            'headers' => [
                'guard' => 'Guard',
                'package' => 'Balík',
                'table' => 'Tabuľka',
                'permission' => 'Povolenie',
            ],
        ],
    ],
];
