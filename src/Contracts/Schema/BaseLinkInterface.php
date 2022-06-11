<?php declare(strict_types=1);

namespace Neomerx\JsonApi\Contracts\Schema;

/**
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

/**
 * @package Neomerx\JsonApi
 */
interface BaseLinkInterface
{
    /** Reserved keyword */
    public const SELF = DocumentInterface::KEYWORD_SELF;
    /** Reserved keyword */
    public const RELATED = DocumentInterface::KEYWORD_RELATED;
    /** Reserved keyword */
    public const FIRST = DocumentInterface::KEYWORD_FIRST;
    /** Reserved keyword */
    public const LAST = DocumentInterface::KEYWORD_LAST;
    /** Reserved keyword */
    public const NEXT = DocumentInterface::KEYWORD_NEXT;
    /** Reserved keyword */
    public const PREV = DocumentInterface::KEYWORD_PREV;
    /** Reserved keyword */
    public const ABOUT = 'about';

    /**
     * If `string` or `array` representation should be used.
     *
     * @return bool
     */
    public function canBeShownAsString(): bool;

    /**
     * Get representation as string.
     *
     * @param string $prefix
     *
     * @return string
     */
    public function getStringRepresentation(string $prefix): string;

    /**
     * Get representation as array.
     *
     * @param string $prefix
     *
     * @return array
     */
    public function getArrayRepresentation(string $prefix): array;
}
