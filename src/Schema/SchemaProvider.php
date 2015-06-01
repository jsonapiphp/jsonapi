<?php namespace Neomerx\JsonApi\Schema;

/**
 * Copyright 2015 info@neomerx.com (www.neomerx.com)
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

use \Closure;
use \Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use \Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use \Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use \Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;

/**
 * @package Neomerx\JsonApi
 */
abstract class SchemaProvider implements SchemaProviderInterface
{
    /** Links information */
    const LINKS = 'links';

    /** Linked data key. */
    const DATA = 'data';

    /** Relationship meta */
    const META = 'meta';

    /** If link should be shown as reference. */
    const SHOW_AS_REF = 'asRef';

    /** If meta information of a resource in relationship should be shown. */
    const SHOW_META = 'showMeta';

    /** If 'self' URL should be shown. */
    const SHOW_SELF = 'showSelf';

    /** If 'related' URL should be shown. */
    const SHOW_RELATED = 'related';

    /** If data should be shown in relationships. */
    const SHOW_DATA = 'showData';

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var string
     */
    protected $baseSelfUrl;

    /**
     * @var bool
     */
    protected $isShowSelf = true;

    /**
     * @var bool
     */
    protected $isShowSelfInIncluded = false;

    /**
     * @var bool
     */
    protected $isShowAttributesInIncluded = true;

    /**
     * @var bool
     */
    protected $isShowRelShipsInIncluded = false;

    /**
     * @var SchemaFactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param SchemaFactoryInterface $factory
     * @param ContainerInterface     $container
     */
    public function __construct(SchemaFactoryInterface $factory, ContainerInterface $container)
    {
        assert('is_string($this->resourceType) && empty($this->resourceType) === false', 'Resource type not set.');
        assert('is_bool($this->isShowSelfInIncluded) && is_bool($this->isShowRelShipsInIncluded)');
        assert('is_string($this->baseSelfUrl) && empty($this->baseSelfUrl) === false', 'Base \'self\' not set.');

        $this->factory   = $factory;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @inheritdoc
     */
    public function getSelfUrl($resource)
    {
        return $this->getBaseSelfUrl($resource).$this->getId($resource);
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryMeta($resource)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipMeta($resource)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getInclusionMeta($resource)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function isShowSelf()
    {
        return $this->isShowSelf;
    }

    /**
     * @inheritdoc
     */
    public function isShowSelfInIncluded()
    {
        return $this->isShowSelfInIncluded;
    }

    /**
     * @inheritdoc
     */
    public function isShowAttributesInIncluded()
    {
        return $this->isShowAttributesInIncluded;
    }

    /**
     * @inheritdoc
     */
    public function isShowRelationshipsInIncluded()
    {
        return $this->isShowRelShipsInIncluded;
    }

    /**
     * Get resource links.
     *
     * @param object $resource
     *
     * @return array
     */
    public function getRelationships($resource)
    {
        $resource ?: null;
        return [];
    }

    /**
     * @inheritdoc
     */
    public function createResourceObject($resource, $isOriginallyArrayed, array $attributeKeysFilter = null)
    {
        return $this->factory->createResourceObject($this, $resource, $isOriginallyArrayed, $attributeKeysFilter);
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipObjectIterator($resource)
    {
        foreach ($this->getRelationships($resource) as $name => $desc) {
            $data          = $this->readData($desc);
            $links         = $this->readLinks($name, $desc);
            $meta          = $this->getValue($desc, self::META, null);
            $isShowMeta    = ($this->getValue($desc, self::SHOW_META, false) === true);
            $isShowSelf    = ($this->getValue($desc, self::SHOW_SELF, false) === true);
            $isShowAsRef   = ($this->getValue($desc, self::SHOW_AS_REF, false) === true);
            $isShowRelated = ($this->getValue($desc, self::SHOW_RELATED, false) === true);
            $isShowData    = ($this->getValue($desc, self::SHOW_DATA, true) === true);

            yield $this->factory->createRelationshipObject(
                $name,
                $data,
                $links,
                $meta,
                $isShowSelf,
                $isShowRelated,
                $isShowMeta,
                $isShowData,
                $isShowAsRef
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getIncludePaths()
    {
        return [];
    }

    /**
     * Get the base self URL
     *
     * @param object $resource
     *
     * @return string
     */
    protected function getBaseSelfUrl($resource)
    {
        $resource ?: null;

        substr($this->baseSelfUrl, -1) === '/' ?: $this->baseSelfUrl .= '/';

        return $this->baseSelfUrl;
    }

    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getValue(array $array, $key, $default = null)
    {
        return (isset($array[$key]) === true ? $array[$key] : $default);
    }

    /**
     * @param array $description
     *
     * @return mixed
     */
    private function readData(array $description)
    {
        $data = $this->getValue($description, self::DATA);
        if ($data instanceof Closure) {
            $data = $data();
        }
        return $data;
    }

    /**
     * @param string $relationshipName
     * @param array  $description
     *
     * @return array<string,LinkInterface>
     */
    private function readLinks($relationshipName, array $description)
    {
        $links = $this->getValue($description, self::LINKS, []);
        if (isset($links[LinkInterface::SELF]) === false) {
            $links[LinkInterface::SELF] = $this->factory->createLink('/relationships/'.$relationshipName);
        }
        if (isset($links[LinkInterface::RELATED]) === false) {
            $links[LinkInterface::RELATED] = $this->factory->createLink('/'.$relationshipName);
        }

        return $links;
    }
}
