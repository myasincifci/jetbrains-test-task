<?php

$container->loadFromExtension('framework', [
    'messenger' => [
        'reset_on_message' =>  true,
        'transports' => [
            'transport_1' => [
                'dsn' => 'null://',
                'failure_transport' => 'failure_transport_1'
            ],
            'transport_2' => 'null://',
            'transport_3' => [
                'dsn' => 'null://',
                'failure_transport' => 'failure_transport_3'
            ],
            'failure_transport_1' => 'null://',
            'failure_transport_3' => 'null://'
        ],
    ],
]);
