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
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Traversable;

trait EncoderPropertiesTrait
{
    /**
     * @var SchemaContainerInterface
     */
    private $container;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var string
     */
    private $urlPrefix;

    /**
     * @var array
     */
    private $includePaths;

    /**
     * @var array
     */
    private $fieldSets;

    /**
     * @var int
     */
    private $encodeOptions;

    /**
     * @var int
     */
    private $encodeDepth;

    /**
     * @var iterable
     */
    private $links;

    /**
     * @var iterable
     */
    private $profile;

    /**
     * @var bool
     */
    private $hasMeta;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * @var string|null
     */
    private $jsonApiVersion;

    /**
     * @var mixed
     */
    private $jsonApiMeta;

    /**
     * @var bool
     */
    private $hasJsonApiMeta;

    /**
     * Reset to initial state.
     *
     * @return self|EncoderInterface
     */
    public function reset(
        string $urlPrefix = Encoder::DEFAULT_URL_PREFIX,
        iterable $includePaths = Encoder::DEFAULT_INCLUDE_PATHS,
        array $fieldSets = Encoder::DEFAULT_FIELD_SET_FILTERS,
        int $encodeOptions = Encoder::DEFAULT_JSON_ENCODE_OPTIONS,
        int $encodeDepth = Encoder::DEFAULT_JSON_ENCODE_DEPTH
    ): EncoderInterface {
        $this->links = null;
        $this->profile = null;
        $this->hasMeta = false;
        $this->meta = null;
        $this->jsonApiVersion = null;
        $this->jsonApiMeta = null;
        $this->hasJsonApiMeta = false;

        $this
            ->withUrlPrefix($urlPrefix)
            ->withIncludedPaths($includePaths)
            ->withFieldSets($fieldSets)
            ->withEncodeOptions($encodeOptions)
            ->withEncodeDepth($encodeDepth);

        return $this;
    }

    public function setContainer(SchemaContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function setFactory(FactoryInterface $factory): self
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withUrlPrefix(string $prefix): EncoderInterface
    {
        $this->urlPrefix = $prefix;

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withIncludedPaths(iterable $paths): EncoderInterface
    {
        $paths = $this->iterableToArray($paths);

        \assert(
            \call_user_func(
                function (array $paths): bool {
                    $pathsOk = true;
                    foreach ($paths as $path) {
                        $pathsOk = true === $pathsOk && true === \is_string($path) && false === empty($path);
                    }

                    return $pathsOk;
                },
                $paths
            )
        );

        $this->includePaths = $paths;

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withFieldSets(array $fieldSets): EncoderInterface
    {
        $this->fieldSets = $fieldSets;

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withEncodeOptions(int $options): EncoderInterface
    {
        $this->encodeOptions = $options;

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withEncodeDepth(int $depth): EncoderInterface
    {
        \assert($depth > 0);

        $this->encodeDepth = $depth;

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withLinks(iterable $links): EncoderInterface
    {
        $this->links = false === $this->hasLinks() ?
            $links :
            $this->links = \array_merge(
                $this->iterableToArray($this->getLinks()),
                $this->iterableToArray($links)
            );

        return $this;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withProfile(iterable $links): EncoderInterface
    {
        $this->profile = $links;

        return $this;
    }

    /**
     * @param mixed $meta
     *
     * @return self|EncoderInterface
     */
    public function withMeta($meta): EncoderInterface
    {
        $this->meta = $meta;
        $this->hasMeta = true;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @return self|EncoderInterface
     */
    public function withJsonApiVersion(string $version): EncoderInterface
    {
        $this->jsonApiVersion = $version;

        return $this;
    }

    /**
     * @param mixed $meta
     *
     * @return self|EncoderInterface
     */
    public function withJsonApiMeta($meta): EncoderInterface
    {
        $this->jsonApiMeta = $meta;
        $this->hasJsonApiMeta = true;

        return $this;
    }

    /**
     * @param mixed $resource
     *
     * @return self|EncoderInterface
     */
    public function withRelationshipSelfLink($resource, string $relationshipName): EncoderInterface
    {
        $link = $this
            ->getSchemaContainer()->getSchema($resource)
            ->getRelationshipSelfLink($resource, $relationshipName);

        return $this->withLinks([
            LinkInterface::SELF => $link,
        ]);
    }

    /**
     * @param mixed $resource
     *
     * @return self|EncoderInterface
     */
    public function withRelationshipRelatedLink($resource, string $relationshipName): EncoderInterface
    {
        $link = $this
            ->getSchemaContainer()->getSchema($resource)
            ->getRelationshipRelatedLink($resource, $relationshipName);

        return $this->withLinks([
            LinkInterface::RELATED => $link,
        ]);
    }

    protected function getSchemaContainer(): SchemaContainerInterface
    {
        return $this->container;
    }

    protected function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    protected function getUrlPrefix(): string
    {
        return $this->urlPrefix;
    }

    protected function getIncludePaths(): array
    {
        return $this->includePaths;
    }

    protected function getFieldSets(): array
    {
        return $this->fieldSets;
    }

    protected function getEncodeOptions(): int
    {
        return $this->encodeOptions;
    }

    protected function getEncodeDepth(): int
    {
        return $this->encodeDepth;
    }

    protected function hasLinks(): bool
    {
        return null !== $this->links;
    }

    protected function getLinks(): iterable
    {
        return $this->links;
    }

    protected function hasProfile(): bool
    {
        return null !== $this->profile;
    }

    protected function getProfile(): iterable
    {
        return $this->profile;
    }

    protected function hasMeta(): bool
    {
        return $this->hasMeta;
    }

    protected function hasJsonApiVersion(): bool
    {
        return null !== $this->jsonApiVersion;
    }

    protected function getJsonApiVersion(): string
    {
        return $this->jsonApiVersion;
    }

    protected function hasJsonApiMeta(): bool
    {
        return $this->hasJsonApiMeta;
    }

    /**
     * @return mixed
     */
    protected function getJsonApiMeta()
    {
        return $this->jsonApiMeta;
    }

    private function iterableToArray(iterable $value): array
    {
        /* @var Traversable|array $value */
        return true === \is_array($value) ? $value : \iterator_to_array($value);
    }
}
