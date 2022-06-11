<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Extensions\Issue169;

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

use Iterator;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Schema\ErrorCollection;

class CustomEncoder extends Encoder
{
    /**
     * @param object|array|Iterator|null $data
     */
    public function serializeData($data): array
    {
        return $this->encodeDataToArray($data);
    }

    /**
     * @param object|array|Iterator|null $data
     */
    public function serializeIdentifiers($data): array
    {
        return $this->encodeIdentifiersToArray($data);
    }

    public function serializeError(ErrorInterface $error): array
    {
        return $this->encodeErrorToArray($error);
    }

    /**
     * @param ErrorInterface[]|ErrorCollection $errors
     */
    public function serializeErrors($errors): array
    {
        return $this->encodeErrorsToArray($errors);
    }

    /**
     * @param array|object $meta
     */
    public function serializeMeta($meta): array
    {
        return $this->encodeMetaToArray($meta);
    }

    protected static function createFactory(): FactoryInterface
    {
        return new CustomFactory();
    }
}
