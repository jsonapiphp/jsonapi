<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Parser;

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
use Neomerx\JsonApi\Contracts\Parser\ParserInterface;
use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Parser\RelationshipData\ParseRelationshipDataTrait;
use Neomerx\JsonApi\Parser\RelationshipData\ParseRelationshipLinksTrait;

class IdentifierAndResource implements ResourceInterface
{
    use ParseRelationshipDataTrait;
    use ParseRelationshipLinksTrait;

    /** @var string */
    public const MSG_NO_SCHEMA_FOUND = 'No Schema found for resource `%s` at path `%s`.';

    /** @var string */
    public const MSG_INVALID_OPERATION = 'Invalid operation.';

    private \Neomerx\JsonApi\Contracts\Parser\EditableContextInterface $context;

    private \Neomerx\JsonApi\Contracts\Schema\PositionInterface $position;

    private \Neomerx\JsonApi\Contracts\Factories\FactoryInterface $factory;

    private \Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface $schemaContainer;

    private \Neomerx\JsonApi\Contracts\Schema\SchemaInterface $schema;

    /**
     * @var mixed
     */
    private $data;

    private ?string $index;

    private string $type;

    /**
     * @var array|null
     */
    private $links = null;

    /**
     * @var array|null
     */
    private $relationshipsCache = null;

    /**
     * @param mixed $data
     */
    public function __construct(
        EditableContextInterface $context,
        PositionInterface $position,
        FactoryInterface $factory,
        SchemaContainerInterface $container,
        $data
    ) {
        \assert($position->getLevel() >= ParserInterface::ROOT_LEVEL);

        $schema = $container->getSchema($data);

        $this->context = $context;
        $this->position = $position;
        $this->factory = $factory;
        $this->schemaContainer = $container;
        $this->schema = $schema;
        $this->data = $data;
        $this->index = $schema->getId($data);
        $this->type = $schema->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(): PositionInterface
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?string
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function hasIdentifierMeta(): bool
    {
        return $this->schema->hasIdentifierMeta($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierMeta()
    {
        return $this->schema->getIdentifierMeta($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): iterable
    {
        $this->context->setPosition($this->position);

        return $this->schema->getAttributes($this->data, $this->context);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UndefinedVariable) PHPMD currently do not support `list` in `[]` syntax
     */
    public function getRelationships(): iterable
    {
        if (null !== $this->relationshipsCache) {
            yield from $this->relationshipsCache;

            return;
        }

        $this->relationshipsCache = [];

        $currentPath = $this->position->getPath();
        $nextLevel = $this->position->getLevel() + 1;
        $nextPathPrefix = true === empty($currentPath) ? '' : $currentPath . PositionInterface::PATH_SEPARATOR;
        $this->context->setPosition($this->position);
        foreach ($this->schema->getRelationships($this->data, $this->context) as $name => $description) {
            \assert(true === $this->assertRelationshipNameAndDescription($name, $description));

            [$hasData, $relationshipData, $nextPosition] = $this->parseRelationshipData(
                $this->factory,
                $this->schemaContainer,
                $this->context,
                $this->type,
                $name,
                $description,
                $nextLevel,
                $nextPathPrefix
            );

            [$hasLinks, $links] =
                $this->parseRelationshipLinks($this->schema, $this->data, $name, $description);

            $hasMeta = \array_key_exists(SchemaInterface::RELATIONSHIP_META, $description);
            $meta = true === $hasMeta ? $description[SchemaInterface::RELATIONSHIP_META] : null;

            \assert(
                $hasData || $hasMeta || $hasLinks,
                "Relationship `${name}` for type `" . $this->getType() .
                '` MUST contain at least one of the following: links, data or meta.'
            );

            $relationship = $this->factory->createRelationship(
                $nextPosition,
                $hasData,
                $relationshipData,
                $hasLinks,
                $links,
                $hasMeta,
                $meta
            );

            $this->relationshipsCache[$name] = $relationship;

            yield $name => $relationship;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasLinks(): bool
    {
        $this->cacheLinks();

        return false === empty($this->links);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinks(): iterable
    {
        $this->cacheLinks();

        return $this->links;
    }

    /**
     * {@inheritdoc}
     */
    public function hasResourceMeta(): bool
    {
        return $this->schema->hasResourceMeta($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceMeta()
    {
        return $this->schema->getResourceMeta($this->data);
    }

    protected function getContext(): EditableContextInterface
    {
        return $this->context;
    }

    /**
     * Read and parse links from schema.
     */
    private function cacheLinks(): void
    {
        if (null === $this->links) {
            $this->links = [];
            foreach ($this->schema->getLinks($this->data) as $name => $link) {
                \assert(true === \is_string($name) && false === empty($name));
                \assert($link instanceof LinkInterface);
                $this->links[$name] = $link;
            }
        }
    }

    private function assertRelationshipNameAndDescription(string $name, array $description): bool
    {
        \assert(
            true === \is_string($name) && false === empty($name),
            'Relationship names for type `' . $this->getType() . '` should be non-empty strings.'
        );
        \assert(
            true === \is_array($description) && false === empty($description),
            "Relationship `${name}` for type `" . $this->getType() . '` should be a non-empty array.'
        );

        return true;
    }
}
