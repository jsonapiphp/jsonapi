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

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\Tests\JsonApi\Data\Models\Author;

final class CustomAuthorSchema extends BaseCustomSchema
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'people';
    }

    /**
     * {@inheritdoc}
     */
    public function getId($resource): ?string
    {
        \assert($resource instanceof Author);

        return (string) $resource->{Author::ATTRIBUTE_ID};
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($resource, ContextInterface $context): iterable
    {
        \assert($resource instanceof Author);

        return [
            Author::ATTRIBUTE_FIRST_NAME => $resource->{Author::ATTRIBUTE_FIRST_NAME},
            Author::ATTRIBUTE_LAST_NAME => $resource->{Author::ATTRIBUTE_LAST_NAME},
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getNonHorrificRelationships($resource, string $currentPath): iterable
    {
        \assert($resource instanceof Author);

        // The whole point of the custom schema is to demonstrate we have access to current path
        // of the resource being parsed and associated field filters and include paths.

        return [
            Author::LINK_COMMENTS => [
                self::RELATIONSHIP_LINKS_RELATED => false,
                self::RELATIONSHIP_HAS_DATA => false,
                self::RELATIONSHIP_DATA => function (): void {
                    throw new \LogicException('I told you, I don\'t have any data.');
                },
                self::RELATIONSHIP_META => [
                    'current_path' => $currentPath,
                    'fields_filter' => $this->getSchemaFields()->getRequestedFields($this->getType()),
                    'relationships_to_include' => $this->getSchemaFields()->getRequestedRelationships($currentPath),
                ],
            ],
        ];
    }
}
