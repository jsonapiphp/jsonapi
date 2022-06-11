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
use Neomerx\JsonApi\Contracts\Schema\LinkWithAliasesInterface;

class LinkWithAliases extends Link implements LinkWithAliasesInterface
{
    private array $aliases;

    private bool $hasAliases;

    /**
     * @param null $meta
     */
    public function __construct(bool $isSubUrl, string $value, iterable $aliases, bool $hasMeta, $meta = null)
    {
        $aliasesArray = [];
        foreach ($aliases as $name => $alias) {
            \assert(true === \is_string($name) && false === empty($name));
            \assert(true === \is_string($alias) && false === empty($alias));
            $aliasesArray[$name] = $alias;
        }

        $this->aliases = $aliasesArray;
        $this->hasAliases = !empty($aliasesArray);

        parent::__construct($isSubUrl, $value, $hasMeta, $meta);
    }

    /**
     * {@inheritdoc}
     */
    public function canBeShownAsString(): bool
    {
        return parent::canBeShownAsString() && false === $this->hasAliases;
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayRepresentation(string $prefix): array
    {
        $linkRepresentation = true === parent::canBeShownAsString() ? [
            DocumentInterface::KEYWORD_HREF => $this->buildUrl($prefix),
        ] : parent::getArrayRepresentation($prefix);

        if (true === $this->hasAliases) {
            $linkRepresentation[DocumentInterface::KEYWORD_ALIASES] = $this->aliases;
        }

        return $linkRepresentation;
    }
}
