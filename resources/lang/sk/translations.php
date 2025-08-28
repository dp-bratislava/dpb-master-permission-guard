<?php

return [
    'title' => 'Master Permission Guard',
    'description' => 'Manage all permissions',
    'filament_page' => [
        'label' => 'MPG',
        'description' => 'Spravujte všetky povolenia',
        'form' => [
            'fields' => [
                'type' => 'Typ',
                'guards' => 'Guard',
                'table' => 'Tabuľka',
                'package' => 'Balíček'
            ],
            'labels' => [
                'all' => 'Všetky'
            ]
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
