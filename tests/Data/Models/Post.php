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

class Post extends stdClass
{
    public const ATTRIBUTE_ID = 'post_id';
    public const ATTRIBUTE_TITLE = 'title';
    public const ATTRIBUTE_BODY = 'body';
    public const LINK_AUTHOR = 'author';
    public const LINK_COMMENTS = 'comments';

    /**
     * @param Author $author
     *
     * @return Post
     */
    public static function instance(
        int $identity,
        string $title,
        string $body,
        Author $author = null,
        array $comments = []
    ) {
        $post = new self();

        $post->{self::ATTRIBUTE_ID} = $identity;
        $post->{self::ATTRIBUTE_TITLE} = $title;
        $post->{self::ATTRIBUTE_BODY} = $body;
        $post->{self::LINK_AUTHOR} = $author;
        $post->{self::LINK_COMMENTS} = $comments;

        return $post;
    }
}
