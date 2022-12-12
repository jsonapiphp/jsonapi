<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Encoder;

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
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Parser\DocumentDataInterface;
use Neomerx\JsonApi\Contracts\Parser\IdentifierInterface;
use Neomerx\JsonApi\Contracts\Parser\ParserInterface;
use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Representation\BaseWriterInterface;
use Neomerx\JsonApi\Contracts\Representation\DocumentWriterInterface;
use Neomerx\JsonApi\Contracts\Representation\ErrorWriterInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Exceptions\InvalidArgumentException;
use Neomerx\JsonApi\Factories\Factory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Encoder implements EncoderInterface
{
    use EncoderPropertiesTrait;

    /**
     * Default value.
     */
    public const DEFAULT_URL_PREFIX = '';

    /**
     * Default value.
     */
    public const DEFAULT_INCLUDE_PATHS = [];

    /**
     * Default value.
     */
    public const DEFAULT_FIELD_SET_FILTERS = [];

    /**
     * Default encode options.
     *
     * @see http://php.net/manual/en/function.json-encode.php
     */
    public const DEFAULT_JSON_ENCODE_OPTIONS = 0;

    /**
     * Default encode depth.
     *
     * @see http://php.net/manual/en/function.json-encode.php
     */
    public const DEFAULT_JSON_ENCODE_DEPTH = 512;

    public function __construct(
        FactoryInterface $factory,
        SchemaContainerInterface $container
    ) {
        $this->setFactory($factory)->setContainer($container)->reset();
    }

    /**
     * Create encoder instance.
     *
     * @param array $schemas schema providers
     */
    public static function instance(array $schemas = []): EncoderInterface
    {
        $factory = static::createFactory();
        $container = $factory->createSchemaContainer($schemas);
        $encoder = $factory->createEncoder($container);

        return $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeData($data): string
    {
        // encode to json
        $array = $this->encodeDataToArray($data);
        $result = $this->encodeToJson($array);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeIdentifiers($data): string
    {
        // encode to json
        $array = $this->encodeIdentifiersToArray($data);
        $result = $this->encodeToJson($array);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeError(ErrorInterface $error): string
    {
        // encode to json
        $array = $this->encodeErrorToArray($error);
        $result = $this->encodeToJson($array);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeErrors(iterable $errors): string
    {
        // encode to json
        $array = $this->encodeErrorsToArray($errors);
        $result = $this->encodeToJson($array);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function encodeMeta($meta): string
    {
        // encode to json
        $array = $this->encodeMetaToArray($meta);
        $result = $this->encodeToJson($array);

        return $result;
    }

    protected static function createFactory(): FactoryInterface
    {
        return new Factory();
    }

    /**
     * @param object|iterable|null $data data to encode
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function encodeDataToArray($data): array
    {
        if (false === \is_array($data) && false === \is_object($data) && null !== $data) {
            throw new InvalidArgumentException();
        }

        $context = $this->getFactory()->createParserContext($this->getFieldSets(), $this->getIncludePaths());
        $parser = $this->getFactory()->createParser($this->getSchemaContainer(), $context);
        $writer = $this->createDocumentWriter();
        $filter = $this->getFactory()->createFieldSetFilter($this->getFieldSets());

        // write header
        $this->writeHeader($writer);

        // write body
        foreach ($parser->parse($data, $this->getIncludePaths()) as $item) {
            if ($item instanceof ResourceInterface) {
                if ($item->getPosition()->getLevel() > ParserInterface::ROOT_LEVEL) {
                    if (true === $filter->shouldOutputRelationship($item->getPosition())) {
                        $writer->addResourceToIncluded($item, $filter);
                    }
                } else {
                    $writer->addResourceToData($item, $filter);
                }
            } elseif ($item instanceof IdentifierInterface) {
                \assert($item->getPosition()->getLevel() <= ParserInterface::ROOT_LEVEL);
                $writer->addIdentifierToData($item);
            } else {
                \assert($item instanceof DocumentDataInterface);
                \assert(0 === $item->getPosition()->getLevel());
                if (true === $item->isCollection()) {
                    $writer->setDataAsArray();
                } elseif (true === $item->isNull()) {
                    $writer->setNullToData();
                }
            }
        }

        // write footer
        $this->writeFooter($writer);

        $array = $writer->getDocument();

        return $array;
    }

    /**
     * @param object|iterable|null $data data to encode
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function encodeIdentifiersToArray($data): array
    {
        $context = $this->getFactory()->createParserContext($this->getFieldSets(), $this->getIncludePaths());
        $parser = $this->getFactory()->createParser($this->getSchemaContainer(), $context);
        $writer = $this->createDocumentWriter();
        $filter = $this->getFactory()->createFieldSetFilter($this->getFieldSets());

        // write header
        $this->writeHeader($writer);

        // write body
        $includePaths = $this->getIncludePaths();
        $expectIncluded = false === empty($includePaths);

        // https://github.com/neomerx/json-api/issues/218
        //
        // if we expect included resources we have to include top level resources in `included` as well
        // Spec:
        //
        // GET /articles/1/relationships/comments?include=comments.author HTTP/1.1
        // Accept: application/vnd.api+json
        //
        // In this case, the primary data would be a collection of resource identifier objects that
        // represent linkage to comments for an article, while the full comments and comment authors
        // would be returned as included data.

        foreach ($parser->parse($data, $includePaths) as $item) {
            if ($item instanceof ResourceInterface) {
                if ($item->getPosition()->getLevel() > ParserInterface::ROOT_LEVEL) {
                    \assert(true === $expectIncluded);
                    if (true === $filter->shouldOutputRelationship($item->getPosition())) {
                        $writer->addResourceToIncluded($item, $filter);
                    }
                } else {
                    $writer->addIdentifierToData($item);
                    if (true === $expectIncluded) {
                        $writer->addResourceToIncluded($item, $filter);
                    }
                }
            } elseif ($item instanceof IdentifierInterface) {
                \assert($item->getPosition()->getLevel() <= ParserInterface::ROOT_LEVEL);
                $writer->addIdentifierToData($item);
            } else {
                \assert($item instanceof DocumentDataInterface);
                \assert(0 === $item->getPosition()->getLevel());
                if (true === $item->isCollection()) {
                    $writer->setDataAsArray();
                } elseif (true === $item->isNull()) {
                    $writer->setNullToData();
                }
            }
        }

        // write footer
        $this->writeFooter($writer);

        $array = $writer->getDocument();

        return $array;
    }

    protected function encodeErrorToArray(ErrorInterface $error): array
    {
        $writer = $this->createErrorWriter();

        // write header
        $this->writeHeader($writer);

        // write body
        $writer->addError($error);

        // write footer
        $this->writeFooter($writer);

        $array = $writer->getDocument();

        return $array;
    }

    protected function encodeErrorsToArray(iterable $errors): array
    {
        $writer = $this->createErrorWriter();

        // write header
        $this->writeHeader($writer);

        // write body
        foreach ($errors as $error) {
            \assert($error instanceof ErrorInterface);
            $writer->addError($error);
        }

        // write footer
        $this->writeFooter($writer);

        // encode to json
        $array = $writer->getDocument();

        return $array;
    }

    protected function encodeMetaToArray($meta): array
    {
        $this->withMeta($meta);

        $writer = $this->getFactory()->createDocumentWriter();

        $writer->setUrlPrefix($this->getUrlPrefix());

        // write header
        $this->writeHeader($writer);

        // write footer
        $this->writeFooter($writer);

        // encode to json
        $array = $writer->getDocument();

        return $array;
    }

    protected function writeHeader(BaseWriterInterface $writer): void
    {
        if (true === $this->hasMeta()) {
            $writer->setMeta($this->getMeta());
        }

        if (true === $this->hasJsonApiVersion()) {
            $writer->setJsonApiVersion($this->getJsonApiVersion());
        }

        if (true === $this->hasJsonApiMeta()) {
            $writer->setJsonApiMeta($this->getJsonApiMeta());
        }

        if (true === $this->hasLinks()) {
            $writer->setLinks($this->getLinks());
        }

        if (true === $this->hasProfile()) {
            $writer->setProfile($this->getProfile());
        }
    }

    protected function writeFooter(BaseWriterInterface $writer): void
    {
        \assert(null !== $writer);
    }

    /**
     * Encode array to JSON.
     */
    protected function encodeToJson(array $document): string
    {
        return \json_encode($document, $this->getEncodeOptions(), $this->getEncodeDepth());
    }

    private function createDocumentWriter(): DocumentWriterInterface
    {
        $writer = $this->getFactory()->createDocumentWriter();
        $writer->setUrlPrefix($this->getUrlPrefix());

        return $writer;
    }

    private function createErrorWriter(): ErrorWriterInterface
    {
        $writer = $this->getFactory()->createErrorWriter();
        $writer->setUrlPrefix($this->getUrlPrefix());

        return $writer;
    }
}
