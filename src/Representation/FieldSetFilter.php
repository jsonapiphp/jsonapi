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

use Neomerx\JsonApi\Contracts\Parser\ResourceInterface;
use Neomerx\JsonApi\Contracts\Representation\FieldSetFilterInterface;
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;

class FieldSetFilter implements FieldSetFilterInterface
{
    private array $fieldSets;

    /**
     * @param array|null $fieldSets
     */
    public function __construct(array $fieldSets)
    {
        $this->fieldSets = [];

        foreach ($fieldSets as $type => $fields) {
            \assert(true === \is_string($type) && false === empty($type));
            \assert(true === \is_iterable($fields));

            $this->fieldSets[$type] = [];

            foreach ($fields as $field) {
                \assert(true === \is_string($field) && false === empty($field));
                \assert(false === isset($this->fieldSets[$type][$field]));

                $this->fieldSets[$type][$field] = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(ResourceInterface $resource): iterable
    {
        yield from $this->filterFields($resource->getType(), $resource->getAttributes());
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationships(ResourceInterface $resource): iterable
    {
        yield from $this->filterFields($resource->getType(), $resource->getRelationships());
    }

    /**
     * {@inheritdoc}
     */
    public function shouldOutputRelationship(PositionInterface $position): bool
    {
        $parentType = $position->getParentType();
        if (true === $this->hasFilter($parentType)) {
            return isset($this->getAllowedFields($parentType)[$position->getParentRelationship()]);
        }

        return true;
    }

    protected function hasFilter(string $type): bool
    {
        return true === isset($this->fieldSets[$type]);
    }

    protected function getAllowedFields(string $type): array
    {
        \assert(true === $this->hasFilter($type));

        return $this->fieldSets[$type];
    }

    protected function filterFields(string $type, iterable $fields): iterable
    {
        if (false === $this->hasFilter($type)) {
            yield from $fields;

            return;
        }

        $allowedFields = $this->getAllowedFields($type);
        foreach ($fields as $name => $value) {
            if (true === isset($allowedFields[$name])) {
                yield $name => $value;
            }
        }
    }
}
