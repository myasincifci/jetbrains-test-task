<?php

$container->loadFromExtension('framework', [
    'http_client' => [
        'scoped_clients' => [
            'foo' => [
                'scope' => '.*',
            ],
        ],
    ],
]);
