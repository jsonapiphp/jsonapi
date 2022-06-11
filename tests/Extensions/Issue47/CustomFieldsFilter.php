<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Extensions\Issue47;

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

use Neomerx\JsonApi\Representation\FieldSetFilter;
use Traversable;

class CustomFieldsFilter extends FieldSetFilter
{
    /**
     * {@inheritdoc}
     */
    protected function filterFields(string $type, iterable $fields): iterable
    {
        if (true === $this->hasFilter($type)) {
            $allowedFields = $this->getAllowedFields($type);
            $fields = $this->iterableToArray($fields);

            $this->deepArrayFilter($fields, $allowedFields, '');
        }

        yield from $fields;
    }

    private function iterableToArray(iterable $iterable): array
    {
        if (true === \is_array($iterable)) {
            return $iterable;
        }
        \assert($iterable instanceof Traversable);

        return \iterator_to_array($iterable);
    }

    private function deepArrayFilter(array &$array, array $filters, string $parentPath): void
    {
        foreach ($array as $key => &$value) {
            $filterKey = true === empty($parentPath) ? $key : $parentPath . '.' . $key;
            if (false === \is_array($value)) {
                if (false === \array_key_exists($filterKey, $filters)) {
                    unset($array[$key]);
                }
            } else {
                $this->deepArrayFilter($value, $filters, $key);
            }
        }
    }
}
