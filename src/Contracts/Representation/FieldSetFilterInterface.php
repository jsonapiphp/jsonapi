<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Contracts\Representation;

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
use Neomerx\JsonApi\Contracts\Schema\PositionInterface;

interface FieldSetFilterInterface
{
    /**
     * Get resource's filtered attributes.
     */
    public function getAttributes(ResourceInterface $resource): iterable;

    /**
     * Get resource's filtered relationships.
     *
     * @see RelationshipInterface
     */
    public function getRelationships(ResourceInterface $resource): iterable;

    /**
     * Spec: Because compound documents require full linkage (except when relationship linkage is
     * excluded by sparse field-sets), intermediate resources in a multi-part path must be
     * returned along with the leaf nodes.
     *
     * This method answers if specific relationship passes field set filters and should be in output.
     */
    public function shouldOutputRelationship(PositionInterface $position): bool;
}
