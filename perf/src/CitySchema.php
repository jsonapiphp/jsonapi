<?php

declare(strict_types=1);

namespace JsonApiPhp\JsonApi\Perf;

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Schema\BaseSchema;

final class CitySchema extends BaseSchema
{
    public function getType(): string
    {
        return 'cities';
    }

    /** @param City $resource */
    public function getId($resource): ?string
    {
        return (string) $resource->id();
    }

    /** @param City $resource */
    public function getAttributes($resource, ContextInterface $context): iterable
    {
        return [
            'name' => $resource->name(),
        ];
    }

    public function getRelationships($resource, ContextInterface $context): iterable
    {
        return [];
    }
}
