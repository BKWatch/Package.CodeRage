<?php

require "vendor/autoload.php";

use DI\ContainerBuilder;
use DI\Container;

use function DI\env;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    'datasouces' => [
        [
            'name' => 'Hal',
            'port' => 90,
            'value' => env('PATH')
        ],
        [
            'name' => 'Doug',
            'port' => 90,
            'value' => env('JAVA_HOME')
        ]
    ]
]);
$container = $builder->build();
var_dump($container->get('datasouces'));
