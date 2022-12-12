<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Parser\RelationshipData;

/*
 * Copyright 2015-2020 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Parser\EditableContextInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipDataInterface;
use Neomerx\JsonApi\Contracts\Schema\IdentifierInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Exceptions\InvalidArgumentException;

use function Neomerx\JsonApi\I18n\format as _;

use Neomerx\JsonApi\Parser\IdentifierAndResource;

trait ParseRelationshipDataTrait
{
    /**
     * @return array [has data, parsed data, next position]
     */
    private function parseRelationshipData(
        FactoryInterface $factory,
        SchemaContainerInterface $container,
        EditableContextInterface $context,
        string $parentType,
        string $name,
        array $description,
        int $nextLevel,
        string $nextPathPrefix
    ): array {
        $hasData = \array_key_exists(SchemaInterface::RELATIONSHIP_DATA, $description);
        // either no data or data should be array/object/null
        \assert(
            false === $hasData ||
            (
                true === \is_array($data = $description[SchemaInterface::RELATIONSHIP_DATA]) ||
                true === \is_object($data) ||
                null === $data
            )
        );

        $nextPosition = $factory->createPosition(
            $nextLevel,
            $nextPathPrefix . $name,
            $parentType,
            $name
        );

        $relationshipData = true === $hasData ? $this->parseData(
            $factory,
            $container,
            $context,
            $nextPosition,
            $description[SchemaInterface::RELATIONSHIP_DATA]
        ) : null;

        return [$hasData, $relationshipData, $nextPosition];
    }

    /**
     * @param mixed $data
     */
    private function parseData(
        FactoryInterface $factory,
        SchemaContainerInterface $container,
        EditableContextInterface $context,
        PositionInterface $position,
        $data
    ): RelationshipDataInterface {
        // support if data is callable (e.g. a closure used to postpone actual data reading)
        if (true === \is_callable($data)) {
            $data = \call_user_func($data);
        }

        if (true === $container->hasSchema($data)) {
            return $factory->createRelationshipDataIsResource($container, $context, $position, $data);
        } elseif ($data instanceof IdentifierInterface) {
            return $factory->createRelationshipDataIsIdentifier($container, $context, $position, $data);
        } elseif (true === \is_array($data)) {
            return $factory->createRelationshipDataIsCollection($container, $context, $position, $data);
        } elseif ($data instanceof \Traversable) {
            return $factory->createRelationshipDataIsCollection(
                $container,
                $context,
                $position,
                $data instanceof \IteratorAggregate ? $data->getIterator() : $data
            );
        } elseif (null === $data) {
            return $factory->createRelationshipDataIsNull();
        }

        throw new InvalidArgumentException(
            _(IdentifierAndResource::MSG_NO_SCHEMA_FOUND, \get_class($data), $position->getPath())
        );
    }
}
