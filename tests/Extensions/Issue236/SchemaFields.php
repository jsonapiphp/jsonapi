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

use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;

final class SchemaFields
{
    /** @var string Path constant */
    private const PATH_SEPARATOR = DocumentInterface::PATH_SEPARATOR;

    /** @var string Path constant */
    private const FIELD_SEPARATOR = ',';

    private ?array $fastRelationships = null;

    private ?array $fastRelationshipLists = null;

    private ?array $fastFields = null;

    private ?array $fastFieldLists = null;

    public function __construct(iterable $paths, iterable $fieldSets)
    {
        foreach ($paths as $path) {
            $separatorPos = \mb_strrpos($path, static::PATH_SEPARATOR);
            if (false === $separatorPos) {
                $curPath = '';
                $relationship = $path;
            } else {
                $curPath = \mb_substr($path, 0, $separatorPos);
                $relationship = \mb_substr($path, $separatorPos + 1);
            }
            $this->fastRelationships[$curPath][$relationship] = true;
            $this->fastRelationshipLists[$curPath][] = $relationship;
        }

        foreach ($fieldSets as $type => $fieldList) {
            foreach (\explode(static::FIELD_SEPARATOR, $fieldList) as $field) {
                $this->fastFields[$type][$field] = true;
                $this->fastFieldLists[$type][] = $field;
            }
        }
    }

    public function isRelationshipRequested(string $currentPath, string $relationship): bool
    {
        return isset($this->fastRelationships[$currentPath][$relationship]);
    }

    public function getRequestedRelationships(string $currentPath): array
    {
        return $this->fastRelationshipLists[$currentPath] ?? [];
    }

    public function isFieldRequested(string $type, string $field): bool
    {
        return false === \array_key_exists($type, $this->fastFields) ? true : isset($this->fastFields[$type][$field]);
    }

    public function getRequestedFields(string $type): ?array
    {
        return $this->fastFieldLists[$type] ?? null;
    }
}
