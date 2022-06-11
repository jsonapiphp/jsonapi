<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Contracts\Parser;

/**
 * Copyright 2015-2020 info@neomerx.com.
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
interface ResourceInterface extends IdentifierInterface
{
    /**
     * Get resource's attributes.
     */
    public function getAttributes(): iterable;

    /**
     * Get resource's relationships.
     *
     * @see RelationshipInterface
     */
    public function getRelationships(): iterable;

    /**
     * If resource has links.
     */
    public function hasLinks(): bool;

    /**
     * Get resource links.
     *
     * @see LinkInterface
     */
    public function getLinks(): iterable;

    /**
     * If resource has meta.
     */
    public function hasResourceMeta(): bool;

    /**
     * Get resource meta.
     *
     * @return mixed
     */
    public function getResourceMeta();
}
