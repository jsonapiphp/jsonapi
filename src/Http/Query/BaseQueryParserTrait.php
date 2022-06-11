<?php

declare(strict_types=1);

namespace Neomerx\JsonApi\Http\Query;

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

use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface as P;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Schema\Error;

trait BaseQueryParserTrait
{
    protected function getIncludes(array $parameters, string $errorTitle): iterable
    {
        if (true === \array_key_exists(P::PARAM_INCLUDE, $parameters)) {
            $includes = $parameters[P::PARAM_INCLUDE];
            $paths = $this->splitCommaSeparatedStringAndCheckNoEmpties(P::PARAM_INCLUDE, $includes, $errorTitle);
            foreach ($paths as $path) {
                yield $path => $this->splitStringAndCheckNoEmpties(P::PARAM_INCLUDE, $path, '.', $errorTitle);
            }
        }
    }

    protected function getIncludePaths(array $parameters, string $errorTitle): iterable
    {
        $aIncludes = $this->getIncludes($parameters, $errorTitle);
        foreach ($aIncludes as $path => $parsed) {
            \assert(null !== $parsed);
            yield $path;
        }
    }

    protected function getFields(array $parameters, string $errorTitle): iterable
    {
        if (true === \array_key_exists(P::PARAM_FIELDS, $parameters)) {
            $fields = $parameters[P::PARAM_FIELDS];
            if (false === \is_array($fields) || true === empty($fields)) {
                throw new JsonApiException($this->createParameterError(P::PARAM_FIELDS, $errorTitle));
            }

            foreach ($fields as $type => $fieldList) {
                yield $type => $this->splitCommaSeparatedStringAndCheckNoEmpties($type, $fieldList, $errorTitle);
            }
        }
    }

    protected function getSorts(array $parameters, string $errorTitle): iterable
    {
        if (true === \array_key_exists(P::PARAM_SORT, $parameters)) {
            $sorts = $parameters[P::PARAM_SORT];
            $values = $this->splitCommaSeparatedStringAndCheckNoEmpties(P::PARAM_SORT, $sorts, $errorTitle);
            foreach ($values as $orderAndField) {
                switch ($orderAndField[0]) {
                    case '-':
                        $isAsc = false;
                        $field = \mb_substr($orderAndField, 1);
                        break;
                    case '+':
                        $isAsc = true;
                        $field = \mb_substr($orderAndField, 1);
                        break;
                    default:
                        $isAsc = true;
                        $field = $orderAndField;
                        break;
                }

                yield $field => $isAsc;
            }
        }
    }

    protected function getProfileUrls(array $parameters, string $errorTitle): iterable
    {
        if (true === \array_key_exists(P::PARAM_PROFILE, $parameters)) {
            $encodedUrls = $parameters[P::PARAM_PROFILE];
            $decodedUrls = \urldecode($encodedUrls);
            yield from $this->splitSpaceSeparatedStringAndCheckNoEmpties(
                P::PARAM_PROFILE,
                $decodedUrls,
                $errorTitle
            );
        }
    }

    /**
     * @param string|mixed $shouldBeString
     */
    private function splitCommaSeparatedStringAndCheckNoEmpties(
        string $paramName,
        $shouldBeString,
        string $errorTitle
    ): iterable {
        return $this->splitStringAndCheckNoEmpties($paramName, $shouldBeString, ',', $errorTitle);
    }

    /**
     * @param string|mixed $shouldBeString
     */
    private function splitSpaceSeparatedStringAndCheckNoEmpties(
        string $paramName,
        $shouldBeString,
        string $errorTitle
    ): iterable {
        return $this->splitStringAndCheckNoEmpties($paramName, $shouldBeString, ' ', $errorTitle);
    }

    /**
     * @param string|mixed $shouldBeString
     *
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    private function splitStringAndCheckNoEmpties(
        string $paramName,
        $shouldBeString,
        string $separator,
        string $errorTitle
    ): iterable {
        if (false === \is_string($shouldBeString) || ($trimmed = \trim($shouldBeString)) === '') {
            throw new JsonApiException($this->createParameterError($paramName, $errorTitle));
        }

        foreach (\explode($separator, $trimmed) as $value) {
            $trimmedValue = \trim($value);
            if ('' === $trimmedValue) {
                throw new JsonApiException($this->createParameterError($paramName, $errorTitle));
            }

            yield $trimmedValue;
        }
    }

    private function createParameterError(string $paramName, string $errorTitle): ErrorInterface
    {
        $source = [Error::SOURCE_PARAMETER => $paramName];
        $error = new Error(null, null, null, null, null, $errorTitle, null, $source);

        return $error;
    }
}
