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
use Neomerx\JsonApi\Contracts\Parser\IdentifierInterface as ParserIdentifierInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipDataInterface;
use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Schema\IdentifierInterface as SchemaIdentifierInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;

abstract class BaseRelationshipData implements RelationshipDataInterface
{
    private \Neomerx\JsonApi\Contracts\Factories\FactoryInterface $factory;

    private \Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface $schemaContainer;

    private \Neomerx\JsonApi\Contracts\Parser\EditableContextInterface $context;

    private \Neomerx\JsonApi\Contracts\Schema\PositionInterface $position;

    public function __construct(
        FactoryInterface $factory,
        SchemaContainerInterface $schemaContainer,
        EditableContextInterface $context,
        PositionInterface $position
    ) {
        $this->factory = $factory;
        $this->schemaContainer = $schemaContainer;
        $this->context = $context;
        $this->position = $position;
    }

    /**
     * @param mixed $resource
     */
    protected function createParsedResource($resource): ResourceInterface
    {
        \assert(
            $this->schemaContainer->hasSchema($resource),
            'No Schema found for resource `' . \get_class($resource) . '`.'
        );

        return $this->factory->createParsedResource(
            $this->context,
            $this->position,
            $this->schemaContainer,
            $resource
        );
    }

    /**
     * @return ResourceInterface
     */
    protected function createParsedIdentifier(SchemaIdentifierInterface $identifier): ParserIdentifierInterface
    {
        return $this->factory->createParsedIdentifier($this->position, $identifier);
    }
}
