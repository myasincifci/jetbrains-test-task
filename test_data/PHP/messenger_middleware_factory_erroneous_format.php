<?php

$container->loadFromExtension('framework', [
    'messenger' => [
        'reset_on_message' =>  true,
        'buses' => [
            'command_bus' => [
                'middleware' => [
                    [
                        'foo' => ['qux'],
                        'bar' => ['baz'],
                    ],
                ],
            ],
        ],
    ],
]);
