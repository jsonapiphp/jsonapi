<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Schema;

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
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

class Link implements LinkInterface
{
    /**
     * If link contains sub-URL value and URL prefix should be added.
     */
    private bool $isSubUrl;

    /**
     * Get link’s URL value (full URL or sub-URL).
     */
    private string $value;

    /**
     * If link has meta information.
     */
    private bool $hasMeta;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * @param mixed $meta
     */
    public function __construct(bool $isSubUrl, string $value, bool $hasMeta, $meta = null)
    {
        $this->isSubUrl = $isSubUrl;
        $this->value = $value;
        $this->hasMeta = $hasMeta;
        $this->meta = $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeShownAsString(): bool
    {
        return false === $this->hasMeta;
    }

    /**
     * {@inheritdoc}
     */
    public function getStringRepresentation(string $prefix): string
    {
        \assert(true === $this->canBeShownAsString());

        return $this->buildUrl($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayRepresentation(string $prefix): array
    {
        \assert(false === $this->canBeShownAsString());

        \assert($this->hasMeta);

        return [
            DocumentInterface::KEYWORD_HREF => $this->buildUrl($prefix),
            DocumentInterface::KEYWORD_META => $this->meta,
        ];
    }

    protected function buildUrl(string $prefix): string
    {
        return $this->isSubUrl ? $prefix . $this->value : $this->value;
    }
}
