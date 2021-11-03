<?php

$container->loadFromExtension('framework', [
    'messenger' => [
        'reset_on_message' =>  true,
        'routing' => [
            'Symfony\Bundle\FrameworkBundle\Tests\Fixtures\Messenger\DummyMessage' => ['amqp'],
        ],
        'transports' => [
            'amqp' => 'amqp://localhost/%2f/messages',
        ],
    ],
]);
