<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Contracts\Factories;

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

use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptMediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Parser\EditableContextInterface;
use Neomerx\JsonApi\Contracts\Parser\IdentifierInterface as ParserIdentifierInterface;
use Neomerx\JsonApi\Contracts\Parser\ParserInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipDataInterface;
use Neomerx\JsonApi\Contracts\Parser\RelationshipInterface;
use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Representation\DocumentWriterInterface;
use Neomerx\JsonApi\Contracts\Representation\ErrorWriterInterface;
use Neomerx\JsonApi\Contracts\Representation\FieldSetFilterInterface;
use Neomerx\JsonApi\Contracts\Schema\IdentifierInterface as SchemaIdentifierInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;

interface FactoryInterface
{
    /**
     * Create encoder.
     */
    public function createEncoder(SchemaContainerInterface $container): EncoderInterface;

    /**
     * Create Schema container.
     */
    public function createSchemaContainer(iterable $schemas): SchemaContainerInterface;

    /**
     * Create resources parser.
     */
    public function createParser(
        SchemaContainerInterface $container,
        EditableContextInterface $context
    ): ParserInterface;

    /**
     * Create position for a parsed result.
     */
    public function createPosition(
        int $level,
        string $path,
        ?string $parentType,
        ?string $parentRelationship
    ): PositionInterface;

    /**
     * Create JSON API document writer.
     */
    public function createDocumentWriter(): DocumentWriterInterface;

    /**
     * Create JSON API error writer.
     */
    public function createErrorWriter(): ErrorWriterInterface;

    /**
     * Create filter for attributes and relationships.
     */
    public function createFieldSetFilter(array $fieldSets): FieldSetFilterInterface;

    /**
     * Create parsed resource over raw resource data.
     *
     * @param mixed $data
     */
    public function createParsedResource(
        EditableContextInterface $context,
        PositionInterface $position,
        SchemaContainerInterface $container,
        $data
    ): ResourceInterface;

    /**
     * Create parsed identifier over raw resource identifier.
     */
    public function createParsedIdentifier(
        PositionInterface $position,
        SchemaIdentifierInterface $identifier
    ): ParserIdentifierInterface;

    /**
     * Create link.
     *
     * @param bool   $isSubUrl if value is either full URL or sub-URL
     * @param string $value    either full URL or sub-URL
     * @param bool   $hasMeta  if links has meta information
     * @param null   $meta     value for meta
     */
    public function createLink(bool $isSubUrl, string $value, bool $hasMeta, $meta = null): LinkInterface;

    /**
     * Create parsed relationship.
     *
     * @param mixed $meta
     */
    public function createRelationship(
        PositionInterface $position,
        bool $hasData,
        ?RelationshipDataInterface $data,
        bool $hasLinks,
        ?iterable $links,
        bool $hasMeta,
        $meta
    ): RelationshipInterface;

    /**
     * Create relationship that represents resource.
     *
     * @param mixed $resource
     */
    public function createRelationshipDataIsResource(
        SchemaContainerInterface $schemaContainer,
        EditableContextInterface $context,
        PositionInterface $position,
        $resource
    ): RelationshipDataInterface;

    /**
     * Create relationship that represents identifier.
     */
    public function createRelationshipDataIsIdentifier(
        SchemaContainerInterface $schemaContainer,
        EditableContextInterface $context,
        PositionInterface $position,
        SchemaIdentifierInterface $identifier
    ): RelationshipDataInterface;

    /**
     * Create relationship that represents collection.
     */
    public function createRelationshipDataIsCollection(
        SchemaContainerInterface $schemaContainer,
        EditableContextInterface $context,
        PositionInterface $position,
        iterable $resources
    ): RelationshipDataInterface;

    /**
     * Create relationship that represents `null`.
     */
    public function createRelationshipDataIsNull(): RelationshipDataInterface;

    /**
     * Create media type.
     *
     * @param array<string,string>|null $parameters
     */
    public function createMediaType(string $type, string $subType, array $parameters = null): MediaTypeInterface;

    /**
     * Create media type for Accept HTTP header.
     *
     * @param array<string,string>|null $parameters
     */
    public function createAcceptMediaType(
        int $position,
        string $type,
        string $subType,
        array $parameters = null,
        float $quality = 1.0
    ): AcceptMediaTypeInterface;

    public function createParserContext(array $fieldSets, array $includePaths): EditableContextInterface;
}
