<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Representation;

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

use Neomerx\JsonApi\Contracts\Parser\IdentifierInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipDataInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipInterface;
use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Representation\DocumentWriterInterface;
use Neomerx\JsonApi\Contracts\Representation\FieldSetFilterInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;

class DocumentWriter extends BaseWriter implements DocumentWriterInterface
{
    /**
     * @var array
     */
    private $addedResources;

    /**
     * {@inheritdoc}
     */
    public function setNullToData(): DocumentWriterInterface
    {
        // check data has not been added yet
        \assert(false === isset($this->data[DocumentInterface::KEYWORD_DATA]));
        $this->data[DocumentInterface::KEYWORD_DATA] = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addIdentifierToData(IdentifierInterface $identifier): DocumentWriterInterface
    {
        $this->addToData($this->getIdentifierRepresentation($identifier));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addResourceToData(
        ResourceInterface $resource,
        FieldSetFilterInterface $filter
    ): DocumentWriterInterface {
        $this->addToData($this->getResourceRepresentation($resource, $filter));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addResourceToIncluded(
        ResourceInterface $resource,
        FieldSetFilterInterface $filter
    ): DocumentWriterInterface {
        // We track resources only in included section to avoid duplicates there.
        // If those resources duplicate main it is not bad because if we remove them
        // (and sometimes we would have to rollback and remove some of them if we meet it in the main resources)
        // the client app will have to search them not only in included section but in the main as well.
        //
        // The spec seems to be OK with it.

        if (true === $this->hasNotBeenAdded($resource)) {
            $this->registerResource($resource);
            $this->addToIncluded($this->getResourceRepresentation($resource, $filter));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function reset(): void
    {
        parent::reset();

        $this->addedResources = [];
    }

    /**
     * If full resource has not been added yet either to includes section.
     */
    protected function hasNotBeenAdded(ResourceInterface $resource): bool
    {
        return false === isset($this->addedResources[$resource->getId()][$resource->getType()]);
    }

    protected function registerResource(ResourceInterface $resource): void
    {
        \assert($this->hasNotBeenAdded($resource));

        $this->addedResources[$resource->getId()][$resource->getType()] = true;
    }

    protected function getIdentifierRepresentation(IdentifierInterface $identifier): array
    {
        // it's odd not to have actual ID for identifier (which is OK for newly created resource).
        \assert(null !== $identifier->getId());

        return false === $identifier->hasIdentifierMeta() ? [
            DocumentInterface::KEYWORD_TYPE => $identifier->getType(),
            DocumentInterface::KEYWORD_ID => $identifier->getId(),
        ] : [
            DocumentInterface::KEYWORD_TYPE => $identifier->getType(),
            DocumentInterface::KEYWORD_ID => $identifier->getId(),
            DocumentInterface::KEYWORD_META => $identifier->getIdentifierMeta(),
        ];
    }

    protected function getIdentifierRepresentationFromResource(ResourceInterface $resource): array
    {
        return false === $resource->hasIdentifierMeta() ? [
            DocumentInterface::KEYWORD_TYPE => $resource->getType(),
            DocumentInterface::KEYWORD_ID => $resource->getId(),
        ] : [
            DocumentInterface::KEYWORD_TYPE => $resource->getType(),
            DocumentInterface::KEYWORD_ID => $resource->getId(),
            DocumentInterface::KEYWORD_META => $resource->getIdentifierMeta(),
        ];
    }

    protected function getAttributesRepresentation(iterable $attributes): array
    {
        $representation = [];
        foreach ($attributes as $name => $value) {
            $representation[$name] = $value;
        }

        return $representation;
    }

    protected function getRelationshipsRepresentation(iterable $relationships): array
    {
        $representation = [];
        foreach ($relationships as $name => $relationship) {
            \assert(true === \is_string($name) && false === empty($name));
            \assert($relationship instanceof RelationshipInterface);
            $representation[$name] = $this->getRelationshipRepresentation($relationship);
        }

        return $representation;
    }

    protected function getRelationshipRepresentation(RelationshipInterface $relationship): array
    {
        $representation = [];

        if (true === $relationship->hasLinks()) {
            $representation[DocumentInterface::KEYWORD_LINKS] =
                $this->getLinksRepresentation($this->getUrlPrefix(), $relationship->getLinks());
        }

        if (true === $relationship->hasData()) {
            $representation[DocumentInterface::KEYWORD_DATA] = $this->getRelationshipDataRepresentation(
                $relationship->getData()
            );
        }

        if (true === $relationship->hasMeta()) {
            $representation[DocumentInterface::KEYWORD_META] = $relationship->getMeta();
        }

        return $representation;
    }

    protected function getRelationshipDataRepresentation(RelationshipDataInterface $data): ?array
    {
        if (true === $data->isResource()) {
            return $this->getIdentifierRepresentationFromResource($data->getResource());
        } elseif (true === $data->isIdentifier()) {
            return $this->getIdentifierRepresentation($data->getIdentifier());
        } elseif (true === $data->isCollection()) {
            $representation = [];
            foreach ($data->getIdentifiers() as $identifier) {
                \assert($identifier instanceof IdentifierInterface);
                $representation[] = $this->getIdentifierRepresentation($identifier);
            }

            return $representation;
        }

        \assert(true === $data->isNull());

        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    protected function getResourceRepresentation(ResourceInterface $resource, FieldSetFilterInterface $filter): array
    {
        $representation = [
            DocumentInterface::KEYWORD_TYPE => $resource->getType(),
        ];

        if (($index = $resource->getId()) !== null) {
            $representation[DocumentInterface::KEYWORD_ID] = $index;
        }

        $attributes = $this->getAttributesRepresentation($filter->getAttributes($resource));
        if (false === empty($attributes)) {
            \assert(
                false !== \json_encode($attributes, JSON_THROW_ON_ERROR),
                'Attributes for resource type `' . $resource->getType() .
                '` cannot be converted into JSON. Please check its Schema returns valid data.'
            );
            $representation[DocumentInterface::KEYWORD_ATTRIBUTES] = $attributes;
        }

        $relationships = $this->getRelationshipsRepresentation($filter->getRelationships($resource));
        if (false === empty($relationships)) {
            \assert(
                false !== \json_encode($relationships, JSON_THROW_ON_ERROR),
                'Relationships for resource type `' . $resource->getType() .
                '` cannot be converted into JSON. Please check its Schema returns valid data.'
            );
            $representation[DocumentInterface::KEYWORD_RELATIONSHIPS] = $relationships;
        }

        if (true === $resource->hasLinks()) {
            $links = $this->getLinksRepresentation($this->getUrlPrefix(), $resource->getLinks());
            \assert(
                false !== \json_encode($links, JSON_THROW_ON_ERROR),
                'Links for resource type `' . $resource->getType() .
                '` cannot be converted into JSON. Please check its Schema returns valid data.'
            );
            $representation[DocumentInterface::KEYWORD_LINKS] = $links;
        }

        if (true === $resource->hasResourceMeta()) {
            $meta = $resource->getResourceMeta();
            \assert(
                false !== \json_encode($meta, JSON_THROW_ON_ERROR),
                'Meta for resource type `' . $resource->getType() .
                '` cannot be converted into JSON. Please check its Schema returns valid data.'
            );
            $representation[DocumentInterface::KEYWORD_META] = $meta;
        }

        return $representation;
    }

    private function addToData(array $representation): void
    {
        if (true === $this->isDataAnArray()) {
            $this->data[DocumentInterface::KEYWORD_DATA][] = $representation;

            return;
        }

        // check data has not been added yet
        \assert(false === \array_key_exists(DocumentInterface::KEYWORD_DATA, $this->data));
        $this->data[DocumentInterface::KEYWORD_DATA] = $representation;
    }

    private function addToIncluded(array $representation): void
    {
        $this->data[DocumentInterface::KEYWORD_INCLUDED][] = $representation;
    }
}
