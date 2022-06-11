<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Data\Models;

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

use stdClass;

class Site extends stdClass
{
    public const ATTRIBUTE_ID = 'site_id';
    public const ATTRIBUTE_NAME = 'name';
    public const LINK_POSTS = 'posts';

    /**
     * @return Site
     */
    public static function instance(int $identity, string $name, array $posts = null)
    {
        $site = new self();

        $site->{self::ATTRIBUTE_ID} = $identity;
        $site->{self::ATTRIBUTE_NAME} = $name;

        null === $posts ?: $site->{self::LINK_POSTS} = $posts;

        return $site;
    }
}
