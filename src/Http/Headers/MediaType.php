<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Http\Headers;

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

use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Exceptions\InvalidArgumentException;

class MediaType implements MediaTypeInterface
{
    /**
     * A list of parameter names for case-insensitive compare. Keys must be lower-cased.
     *
     * @var array
     */
    protected const PARAMETER_NAMES = [
        'charset' => true,
    ];
    private string $type;

    private string $subType;

    /**
     * @var string?
     */
    private ?string $mediaType = null;

    /**
     * @var array<string,string>|null
     */
    private ?array $parameters;

    /**
     * @param array<string,string>|null $parameters
     */
    public function __construct(string $type, string $subType, array $parameters = null)
    {
        $type = \trim($type);
        if (true === empty($type)) {
            throw new InvalidArgumentException('type');
        }

        $subType = \trim($subType);
        if (true === empty($subType)) {
            throw new InvalidArgumentException('subType');
        }

        $this->type = $type;
        $this->subType = $subType;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubType(): string
    {
        return $this->subType;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaType(): string
    {
        if (null === $this->mediaType) {
            $this->mediaType = $this->type . '/' . $this->getSubType();
        }

        return $this->mediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesTo(MediaTypeInterface $mediaType): bool
    {
        return
            $this->isTypeMatches($mediaType) &&
            $this->isSubTypeMatches($mediaType) &&
            $this->isMediaParametersMatch($mediaType);
    }

    /**
     * {@inheritdoc}
     */
    public function equalsTo(MediaTypeInterface $mediaType): bool
    {
        return
            $this->isTypeEquals($mediaType) &&
            $this->isSubTypeEquals($mediaType) &&
            $this->isMediaParametersEqual($mediaType);
    }

    private function isTypeMatches(MediaTypeInterface $mediaType): bool
    {
        return '*' === $mediaType->getType() || $this->isTypeEquals($mediaType);
    }

    private function isTypeEquals(MediaTypeInterface $mediaType): bool
    {
        // Type, subtype and param name should be compared case-insensitive
        // https://tools.ietf.org/html/rfc7231#section-3.1.1.1
        return 0 === \strcasecmp($this->type, $mediaType->getType());
    }

    private function isSubTypeMatches(MediaTypeInterface $mediaType): bool
    {
        return '*' === $mediaType->getSubType() || $this->isSubTypeEquals($mediaType);
    }

    private function isSubTypeEquals(MediaTypeInterface $mediaType): bool
    {
        // Type, subtype and param name should be compared case-insensitive
        // https://tools.ietf.org/html/rfc7231#section-3.1.1.1
        return 0 === \strcasecmp($this->getSubType(), $mediaType->getSubType());
    }

    private function isMediaParametersMatch(MediaTypeInterface $mediaType): bool
    {
        if (true === $this->bothMediaTypeParamsEmpty($mediaType)) {
            return true;
        } elseif ($this->bothMediaTypeParamsNotEmptyAndEqualInSize($mediaType)) {
            // Type, subtype and param name should be compared case-insensitive
            // https://tools.ietf.org/html/rfc7231#section-3.1.1.1
            $ourParameters = \array_change_key_case($this->parameters);
            $parametersToCompare = \array_change_key_case($mediaType->getParameters());

            // if at least one name are different they are not equal
            if (false === empty(\array_diff_key($ourParameters, $parametersToCompare))) {
                return false;
            }

            // If we are here we have to compare values. Also some of the values should be compared case-insensitive
            // according to https://tools.ietf.org/html/rfc7231#section-3.1.1.1
            // > 'Parameter values might or might not be case-sensitive, depending on
            // the semantics of the parameter name.'
            foreach ($ourParameters as $name => $value) {
                if (false === $this->paramValuesMatch($name, $value, $parametersToCompare[$name])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function isMediaParametersEqual(MediaTypeInterface $mediaType): bool
    {
        if (true === $this->bothMediaTypeParamsEmpty($mediaType)) {
            return true;
        } elseif ($this->bothMediaTypeParamsNotEmptyAndEqualInSize($mediaType)) {
            // Type, subtype and param name should be compared case-insensitive
            // https://tools.ietf.org/html/rfc7231#section-3.1.1.1
            $ourParameters = \array_change_key_case($this->parameters);
            $parametersToCompare = \array_change_key_case($mediaType->getParameters());

            // if at least one name are different they are not equal
            if (false === empty(\array_diff_key($ourParameters, $parametersToCompare))) {
                return false;
            }

            // If we are here we have to compare values. Also some of the values should be compared case-insensitive
            // according to https://tools.ietf.org/html/rfc7231#section-3.1.1.1
            // > 'Parameter values might or might not be case-sensitive, depending on
            // the semantics of the parameter name.'
            foreach ($ourParameters as $name => $value) {
                if (false === $this->paramValuesEqual($name, $value, $parametersToCompare[$name])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function bothMediaTypeParamsEmpty(MediaTypeInterface $mediaType): bool
    {
        return null === $this->parameters && null === $mediaType->getParameters();
    }

    private function bothMediaTypeParamsNotEmptyAndEqualInSize(MediaTypeInterface $mediaType): bool
    {
        $pr1 = $this->parameters;
        $pr2 = $mediaType->getParameters();

        return (false === empty($pr1) && false === empty($pr2)) && (\count($pr1) === \count($pr2));
    }

    private function isParamCaseInsensitive(string $name): bool
    {
        return isset(static::PARAMETER_NAMES[$name]);
    }

    private function paramValuesEqual(string $name, string $value, string $valueToCompare): bool
    {
        $valuesEqual = true === $this->isParamCaseInsensitive($name) ?
            0 === \strcasecmp($value, $valueToCompare) : $value === $valueToCompare;

        return $valuesEqual;
    }

    private function paramValuesMatch(string $name, string $value, string $valueToCompare): bool
    {
        $valuesEqual = '*' === $valueToCompare || $this->paramValuesEqual($name, $value, $valueToCompare);

        return $valuesEqual;
    }
}
