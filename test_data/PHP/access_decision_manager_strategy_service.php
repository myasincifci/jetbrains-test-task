<?php

$container->loadFromExtension('security', [
    'enable_authenticator_manager' => true,
    'access_decision_manager' => [
        'strategy_service' => 'app.custom_access_decision_strategy',
    ],
    'providers' => [
        'default' => [
            'memory' => [
                'users' => [
                    'foo' => ['password' => 'foo', 'roles' => 'ROLE_USER'],
                ],
            ],
        ],
    ],
    'firewalls' => [
        'simple' => ['pattern' => '/login', 'security' => false],
    ],
]);
