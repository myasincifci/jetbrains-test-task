<?php

$container->loadFromExtension('framework', [
    'property_access' => [
        'magic_call' => true,
        'magic_get' => true,
        'magic_set' => false,
        'throw_exception_on_invalid_index' => true,
        'throw_exception_on_invalid_property_path' => false,
    ],
]);
