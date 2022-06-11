<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Extensions\Issue236;

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
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Parser\IdentifierAndResource;
use Neomerx\JsonApi\Parser\RelationshipData\ParseRelationshipDataTrait;
use Neomerx\JsonApi\Parser\RelationshipData\ParseRelationshipLinksTrait;

final class CustomIdentifierAndResource extends IdentifierAndResource
{
    use ParseRelationshipDataTrait;
    use ParseRelationshipLinksTrait;

    /**
     * @var FactoryInterface
     */
    private $factory;

    private \Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface $container;

    /**
     * @var BaseCustomSchema
     */
    private $schema;

    /**
     * @var mixed
     */
    private $data;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EditableContextInterface $context,
        PositionInterface $position,
        FactoryInterface $factory,
        SchemaContainerInterface $container,
        $data
    ) {
        parent::__construct($context, $position, $factory, $container, $data);

        $this->factory = $factory;
        $this->container = $container;
        $this->data = $data;

        $this->schema = $container->getSchema($data);

        \assert($this->schema instanceof BaseCustomSchema);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationships(): iterable
    {
        $currentPath = $this->getPosition()->getPath();
        $nextLevel = $this->getPosition()->getLevel() + 1;
        $nextPathPrefix = true === empty($currentPath) ? '' : $currentPath . PositionInterface::PATH_SEPARATOR;
        foreach ($this->schema->getNonHorrificRelationships($this->data, $currentPath) as $name => $description) {
            if (true === \array_key_exists(BaseCustomSchema::RELATIONSHIP_HAS_DATA, $description) &&
                false === $description[BaseCustomSchema::RELATIONSHIP_HAS_DATA]) {
                unset($description[BaseCustomSchema::RELATIONSHIP_DATA]);
            }
            [$hasData, $relationshipData, $nextPosition] = $this->parseRelationshipData(
                $this->factory,
                $this->container,
                $this->getContext(),
                $this->getType(),
                $name,
                $description,
                $nextLevel,
                $nextPathPrefix
            );

            [$hasLinks, $links] = $this->parseRelationshipLinks($this->schema, $this->data, $name, $description);

            $hasMeta = \array_key_exists(SchemaInterface::RELATIONSHIP_META, $description);
            $meta = true === $hasMeta ? $description[SchemaInterface::RELATIONSHIP_META] : null;

            $relationship = $this->factory->createRelationship(
                $nextPosition,
                $hasData,
                $relationshipData,
                $hasLinks,
                $links,
                $hasMeta,
                $meta
            );

            yield $name => $relationship;
        }
    }
}
