<?php

$container->loadFromExtension('security', [
    'enable_authenticator_manager' => true,
    'firewalls' => [
        'main' => [
            'form_login' => [
                'login_path' => '/login',
            ],
        ],
    ],
    'role_hierarchy' => [
        'FOO' => 'BAR',
        'ADMIN' => 'USER',
    ],
]);
