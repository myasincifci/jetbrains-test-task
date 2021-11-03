<?php

$container->loadFromExtension('security', [
    'enable_authenticator_manager' => true,
    'providers' => [
        'default' => ['id' => 'foo'],
    ],

    'firewalls' => [
        'main' => [
            'form_login' => true,
            'remember_me' => [
                'secret' => 'TheSecret',
                'catch_exceptions' => false,
                'token_provider' => 'token_provider_id',
            ],
        ],
    ],
]);
