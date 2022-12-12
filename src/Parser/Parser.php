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
use Neomerx\JsonApi\Contracts\Parser\DocumentDataInterface;
use Neomerx\JsonApi\Contracts\Parser\EditableContextInterface;
use Neomerx\JsonApi\Contracts\Parser\IdentifierInterface;
use Neomerx\JsonApi\Contracts\Parser\ParserInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipInterface;
use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\IdentifierInterface as SchemaIdentifierInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Exceptions\InvalidArgumentException;

use function Neomerx\JsonApi\I18n\format as _;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Parser implements ParserInterface
{
    /** @var string */
    public const MSG_NO_SCHEMA_FOUND = 'No Schema found for top-level resource `%s`.';

    /** @var string */
    public const MSG_NO_DATA_IN_RELATIONSHIP =
        'For resource of type `%s` with ID `%s` relationship `%s` cannot be parsed because it has no data. Skipping.';

    /** @var string */
    public const MSG_CAN_NOT_PARSE_RELATIONSHIP =
        'For resource of type `%s` with ID `%s` relationship `%s` cannot be parsed because it either ' .
        'has `null` or identifier as data. Skipping.';

    /** @var string */
    public const MSG_PATHS_HAVE_NOT_BEEN_NORMALIZED_YET =
        'Paths have not been normalized yet. Have you called `parse` method already?';

    private \Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface $schemaContainer;

    private \Neomerx\JsonApi\Contracts\Factories\FactoryInterface $factory;

    private ?array $paths = null;

    private array $resourcesTracker = [];

    private \Neomerx\JsonApi\Contracts\Parser\EditableContextInterface $context;

    public function __construct(
        FactoryInterface $factory,
        SchemaContainerInterface $container,
        EditableContextInterface $context
    ) {
        $this->factory = $factory;
        $this->schemaContainer = $container;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function parse($data, array $paths = []): iterable
    {
        \assert(true === \is_array($data) || true === \is_object($data) || null === $data);

        $this->paths = $this->normalizePaths($paths);

        $rootPosition = $this->factory->createPosition(
            ParserInterface::ROOT_LEVEL,
            ParserInterface::ROOT_PATH,
            null,
            null
        );

        if (true === $this->schemaContainer->hasSchema($data)) {
            yield $this->createDocumentDataIsResource($rootPosition);
            yield from $this->parseAsResource($rootPosition, $data);
        } elseif ($data instanceof SchemaIdentifierInterface) {
            yield $this->createDocumentDataIsIdentifier($rootPosition);
            yield $this->parseAsIdentifier($rootPosition, $data);
        } elseif (true === \is_array($data)) {
            yield $this->createDocumentDataIsCollection($rootPosition);
            yield from $this->parseAsResourcesOrIdentifiers($rootPosition, $data);
        } elseif ($data instanceof \Traversable) {
            $data = $data instanceof \IteratorAggregate ? $data->getIterator() : $data;
            yield $this->createDocumentDataIsCollection($rootPosition);
            yield from $this->parseAsResourcesOrIdentifiers($rootPosition, $data);
        } elseif (null === $data) {
            yield $this->createDocumentDataIsNull($rootPosition);
        } else {
            throw new InvalidArgumentException(_(static::MSG_NO_SCHEMA_FOUND, \get_class($data)));
        }
    }

    protected function getContext(): EditableContextInterface
    {
        return $this->context;
    }

    protected function getNormalizedPaths(): array
    {
        \assert(null !== $this->paths, _(static::MSG_PATHS_HAVE_NOT_BEEN_NORMALIZED_YET));

        return $this->paths;
    }

    protected function isPathRequested(string $path): bool
    {
        return isset($this->paths[$path]);
    }

    /**
     * @see ResourceInterface
     * @see IdentifierInterface
     */
    private function parseAsResourcesOrIdentifiers(
        PositionInterface $position,
        iterable $dataOrIds
    ): iterable {
        foreach ($dataOrIds as $dataOrId) {
            if (true === $this->schemaContainer->hasSchema($dataOrId)) {
                yield from $this->parseAsResource($position, $dataOrId);

                continue;
            }

            \assert($dataOrId instanceof SchemaIdentifierInterface);
            yield $this->parseAsIdentifier($position, $dataOrId);
        }
    }

    /**
     * @param mixed $data
     *
     * @see ResourceInterface
     */
    private function parseAsResource(
        PositionInterface $position,
        $data
    ): iterable {
        \assert(true === $this->schemaContainer->hasSchema($data));

        $resource = $this->factory->createParsedResource(
            $this->getContext(),
            $position,
            $this->schemaContainer,
            $data
        );

        yield from $this->parseResource($resource);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function parseResource(ResourceInterface $resource): iterable
    {
        $id = $resource->getId();
        $type = $resource->getType();
        $seenBefore = isset($this->resourcesTracker[$id][$type]);

        // top level resources should be yielded in any case as it could be an array of the resources
        // for deeper levels it's not needed as they go to `included` section and it must have no more
        // than one instance of the same resource.

        if (false === $seenBefore || $resource->getPosition()->getLevel() <= ParserInterface::ROOT_LEVEL) {
            yield $resource;
        }

        // parse relationships only for resources not seen before (prevents infinite loop for circular references)
        if (false === $seenBefore) {
            // remember by id and type
            $this->resourcesTracker[$id][$type] = true;

            foreach ($resource->getRelationships() as $name => $relationship) {
                \assert(\is_string($name));
                \assert($relationship instanceof RelationshipInterface);

                $isShouldParse = $this->isPathRequested($relationship->getPosition()->getPath());

                if (true === $isShouldParse && true === $relationship->hasData()) {
                    $relData = $relationship->getData();
                    if (true === $relData->isResource()) {
                        yield from $this->parseResource($relData->getResource());

                        continue;
                    }

                    if (true === $relData->isCollection()) {
                        foreach ($relData->getResources() as $relResource) {
                            \assert($relResource instanceof ResourceInterface ||
                                $relResource instanceof IdentifierInterface);
                            if ($relResource instanceof ResourceInterface) {
                                yield from $this->parseResource($relResource);
                            }
                        }

                        continue;
                    }

                    \assert($relData->isNull() || $relData->isIdentifier());
                }
            }
        }
    }

    private function parseAsIdentifier(
        PositionInterface $position,
        SchemaIdentifierInterface $identifier
    ): IdentifierInterface {
        return new class($position, $identifier) implements IdentifierInterface {
            private \Neomerx\JsonApi\Contracts\Schema\PositionInterface $position;

            private SchemaIdentifierInterface $identifier;

            public function __construct(PositionInterface $position, SchemaIdentifierInterface $identifier)
            {
                $this->position = $position;
                $this->identifier = $identifier;
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
                return $this->identifier->getId();
            }

            /**
             * {@inheritdoc}
             */
            public function getType(): string
            {
                return $this->identifier->getType();
            }

            /**
             * {@inheritdoc}
             */
            public function hasIdentifierMeta(): bool
            {
                return $this->identifier->hasIdentifierMeta();
            }

            /**
             * {@inheritdoc}
             */
            public function getIdentifierMeta()
            {
                return $this->identifier->getIdentifierMeta();
            }
        };
    }

    private function createDocumentDataIsCollection(PositionInterface $position): DocumentDataInterface
    {
        return new ParsedDocumentData(
            $position,
            true,
            false,
        );
    }

    private function createDocumentDataIsNull(PositionInterface $position): DocumentDataInterface
    {
        return new ParsedDocumentData(
            $position,
            false,
            true,
        );
    }

    private function createDocumentDataIsResource(PositionInterface $position): DocumentDataInterface
    {
        return new ParsedDocumentData(
            $position,
            false,
            false,
        );
    }

    private function createDocumentDataIsIdentifier(PositionInterface $position): DocumentDataInterface
    {
        return new ParsedDocumentData(
            $position,
            false,
            false,
        );
    }

    private function normalizePaths(iterable $paths): array
    {
        $separator = DocumentInterface::PATH_SEPARATOR;

        // convert paths like a.b.c to paths that actually should be used a, a.b, a.b.c
        $normalizedPaths = [];
        foreach ($paths as $path) {
            $curPath = '';
            foreach (\explode($separator, $path) as $pathPart) {
                $curPath = true === empty($curPath) ? $pathPart : $curPath . $separator . $pathPart;
                $normalizedPaths[$curPath] = true;
            }
        }

        return $normalizedPaths;
    }
}
