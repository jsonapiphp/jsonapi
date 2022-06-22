#!/usr/bin/env php
<?php

declare(strict_types=1);

use JsonApiPhp\JsonApi\Perf\City;
use JsonApiPhp\JsonApi\Perf\CitySchema;
use Neomerx\JsonApi\Encoder\Encoder;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$data = \array_map(
    static fn(int $id): City => new City($id, 'Warsaw'),
    \range(1, 1000),
);
$encoder = Encoder::instance(
    [
        City::class => CitySchema::class,
    ]
);
$encoder->encodeData($data);
