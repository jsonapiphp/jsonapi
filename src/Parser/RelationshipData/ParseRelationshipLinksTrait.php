<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Parser\RelationshipData;

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

use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;

trait ParseRelationshipLinksTrait
{
    /**
     * @param mixed $parentData
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function parseRelationshipLinks(
        SchemaInterface $parentSchema,
        $parentData,
        string $name,
        array $description
    ): array {
        $addSelfLink = $description[SchemaInterface::RELATIONSHIP_LINKS_SELF] ??
            $parentSchema->isAddSelfLinkInRelationshipByDefault($name);
        $addRelatedLink = $description[SchemaInterface::RELATIONSHIP_LINKS_RELATED] ??
            $parentSchema->isAddRelatedLinkInRelationshipByDefault($name);
        \assert(true === \is_bool($addSelfLink) || $addSelfLink instanceof LinkInterface);
        \assert(true === \is_bool($addRelatedLink) || $addRelatedLink instanceof LinkInterface);

        $schemaLinks = $description[SchemaInterface::RELATIONSHIP_LINKS] ?? [];
        \assert(\is_array($schemaLinks));

        // if `self` or `related` link was given as LinkInterface merge it with the other links
        $extraSchemaLinks = null;
        if (false === \is_bool($addSelfLink)) {
            $extraSchemaLinks[LinkInterface::SELF] = $addSelfLink;
            $addSelfLink = false;
        }
        if (false === \is_bool($addRelatedLink)) {
            $extraSchemaLinks[LinkInterface::RELATED] = $addRelatedLink;
            $addRelatedLink = false;
        }
        if (false === empty($extraSchemaLinks)) {
            // IDE do not understand it's defined without he line below
            \assert(isset($extraSchemaLinks));
            $schemaLinks = \array_merge($extraSchemaLinks, $schemaLinks);
            unset($extraSchemaLinks);
        }
        \assert(true === \is_bool($addSelfLink) && true === \is_bool($addRelatedLink));

        $hasLinks = true === $addSelfLink || true === $addRelatedLink || false === empty($schemaLinks);
        $links = true === $hasLinks ?
            $this->parseLinks($parentSchema, $parentData, $name, $schemaLinks, $addSelfLink, $addRelatedLink) : null;

        return [$hasLinks, $links];
    }

    /**
     * @param mixed $parentData
     */
    private function parseLinks(
        SchemaInterface $parentSchema,
        $parentData,
        string $relationshipName,
        iterable $schemaLinks,
        bool $addSelfLink,
        bool $addRelatedLink
    ): iterable {
        $gotSelf = false;
        $gotRelated = false;

        foreach ($schemaLinks as $name => $link) {
            \assert($link instanceof LinkInterface);
            if (LinkInterface::SELF === $name) {
                \assert(false === $gotSelf);
                $gotSelf = true;
                $addSelfLink = false;
            } elseif (LinkInterface::RELATED === $name) {
                \assert(false === $gotRelated);
                $gotRelated = true;
                $addRelatedLink = false;
            }

            yield $name => $link;
        }

        if (true === $addSelfLink) {
            $link = $parentSchema->getRelationshipSelfLink($parentData, $relationshipName);
            yield LinkInterface::SELF => $link;
            $gotSelf = true;
        }
        if (true === $addRelatedLink) {
            $link = $parentSchema->getRelationshipRelatedLink($parentData, $relationshipName);
            yield LinkInterface::RELATED => $link;
            $gotRelated = true;
        }

        // spec: check links has at least one of the following: self or related
        \assert($gotSelf || $gotRelated);
    }
}
