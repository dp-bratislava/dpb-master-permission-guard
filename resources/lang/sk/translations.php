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
                'package' => 'Balíček',
            ],
            'labels' => [
                'all' => 'Všetky',
            ],
        ],
        'table' => [
            'headers' => [
                'guard' => 'Guard',
                'table' => 'Tabuľka',
                'action' => 'Akcia',
                'permission' => 'Povolenie',
                'roles' => 'Roly',
            ],
        ],
        'actions' => [
            'manage_assigned_roles' => [
                'label' => 'Priradené roly [:count]',
                'modal_heading' => 'Správa priradených rolí',
                'roles_form_field' => 'Roly',
                'notifications' => [
                    'success' => 'Roly boli úspešne priradené k povoleniu.',
                ],
            ],
        ],
    ],
    'permission_actions' => [
        'create' => 'Vytvoriť',
        'read' => 'Čítať',
        'update' => 'Aktualizovať',
        'delete' => 'Odstrániť',
        'restore' => 'Obnoviť',
    ],
];
