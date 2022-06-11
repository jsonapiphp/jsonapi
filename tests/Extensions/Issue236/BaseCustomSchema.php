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
use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Schema\BaseSchema;

abstract class BaseCustomSchema extends BaseSchema
{
    use SchemaFieldsTrait;

    /** @var int If data should be really used */
    public const RELATIONSHIP_HAS_DATA = self::RELATIONSHIP_LINKS_RELATED + 1;

    public function __construct(FactoryInterface $factory, SchemaFields $fields)
    {
        parent::__construct($factory);

        $this->setSchemaFields($fields);
    }

    /**
     * @param mixed $resource
     */
    abstract public function getNonHorrificRelationships($resource, string $currentPath): iterable;

    /**
     * {@inheritdoc}
     */
    public function getRelationships($resource, ContextInterface $context): iterable
    {
        throw new \LogicException('Use `getNonHorrificRelationships` instead.');
    }
}
