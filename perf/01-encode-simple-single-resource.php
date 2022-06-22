#!/usr/bin/env php
<?php

declare(strict_types=1);

use JsonApiPhp\JsonApi\Perf\City;
use JsonApiPhp\JsonApi\Perf\CitySchema;
use Neomerx\JsonApi\Encoder\Encoder;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$data = new City(1, 'Warsaw');
$encoder = Encoder::instance(
    [
        City::class => CitySchema::class,
    ]
);
$encoder->encodeData($data);
