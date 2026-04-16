<?php

return [
    'default' => 'user',

    'roles' => [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'user' => 'User',
    ],

    'permissions' => [
        'admin' => ['*'],
        'manager' => [
            'view-pages',
            'view-subscription',
            'create-subscription',
            'manage-facebook',
        ],
        'user' => [
            'view-pages',
            'view-subscription',
        ],
    ],
];
