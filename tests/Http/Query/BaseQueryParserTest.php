<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Http\Query;

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

use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Http\Query\BaseQueryParser;
use Neomerx\Tests\JsonApi\BaseTestCase;
use Neomerx\Tests\JsonApi\Data\Models\Author;
use Neomerx\Tests\JsonApi\Data\Models\Comment;
use Neomerx\Tests\JsonApi\Data\Schemas\AuthorSchema;
use Neomerx\Tests\JsonApi\Data\Schemas\CommentSchema;

class BaseQueryParserTest extends BaseTestCase
{
    /**
     * Test query.
     */
    public function test_empty_query_params(): void
    {
        $queryParameters = [];

        $parser = $this->createParser($queryParameters);

        self::assertEquals([], $this->iterableToArray($parser->getIncludes()));
        self::assertEquals([], $this->iterableToArray($parser->getFields()));
    }

    /**
     * Test query.
     */
    public function test_includes(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => 'comments,   comments.author',
        ];

        $parser = $this->createParser($queryParameters);

        self::assertEquals(
            [
                'comments' => ['comments'],
                'comments.author' => ['comments', 'author'],
            ],
            $this->iterableToArray($parser->getIncludes())
        );
    }

    /**
     * Test query.
     */
    public function test_include_paths(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => 'comments,   comments.author',
        ];

        $parser = $this->createParser($queryParameters);

        self::assertEquals(
            [
                'comments',
                'comments.author',
            ],
            $this->iterableToArray($parser->getIncludePaths())
        );
    }

    /**
     * That's a special case to test possible issues with `empty` function which thinks "0" is an empty string.
     */
    public function test_includes_for_string_with_zeroes1(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => '0',
        ];

        $parser = $this->createParser($queryParameters);

        self::assertEquals(
            [
                '0' => ['0'],
            ],
            $this->iterableToArray($parser->getIncludes())
        );
    }

    /**
     * That's a special case to test possible issues with `empty` function which thinks "0" is an empty string.
     */
    public function test_includes_for_string_with_zeroes2(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => '0,1',
        ];

        $parser = $this->createParser($queryParameters);

        self::assertEquals(
            [
                '0' => ['0'],
                '1' => ['1'],
            ],
            $this->iterableToArray($parser->getIncludes())
        );
    }

    /**
     * Test query.
     */
    public function test_fields(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_FIELDS => [
                'articles' => 'title,     body      ',
                'people' => 'name',
            ],
        ];

        $parser = $this->createParser($queryParameters);

        self::assertEquals(
            [
                'articles' => ['title', 'body'],
                'people' => ['name'],
            ],
            $this->iterableToArray($parser->getFields())
        );
    }

    /**
     * Test query.
     */
    public function test_sorts(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_SORT => '-created,title,+updated',
        ];

        $parser = $this->createParser($queryParameters);

        self::assertEquals(
            [
                'created' => false,
                'title' => true,
                'updated' => true,
            ],
            $this->iterableToArray($parser->getSorts())
        );
    }

    /**
     * Test query.
     */
    public function test_invalid_includes_empty_value(): void
    {
        $this->expectException(JsonApiException::class);

        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => 'comments,      ,comments.author',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     */
    public function test_invalid_includes_not_string1(): void
    {
        $this->expectException(JsonApiException::class);

        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => ['not string'],
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     */
    public function test_invalid_includes_not_string2(): void
    {
        $this->expectException(JsonApiException::class);

        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => null,
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     */
    public function test_invalid_includes_empty_string1(): void
    {
        $this->expectException(JsonApiException::class);

        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => '',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     */
    public function test_invalid_includes_empty_string2(): void
    {
        $this->expectException(JsonApiException::class);

        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => '  ',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     */
    public function test_invalid_fields(): void
    {
        $this->expectException(JsonApiException::class);

        $queryParameters = [
            BaseQueryParser::PARAM_FIELDS => 'not array',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getFields());
    }

    /**
     * Shows how to integrate base query parser with EncodingParameters.
     *
     * @see https://github.com/neomerx/json-api/issues/198
     */
    public function test_integration_with_encoding_parameters(): void
    {
        $profileUrl1 = 'http://example1.com/foo';
        $profileUrl2 = 'http://example2.com/boo';

        $queryParameters = [
            BaseQueryParser::PARAM_FIELDS => [
                'comments' => Comment::LINK_AUTHOR . ',     ' . Comment::ATTRIBUTE_BODY . '      ',
                'people' => Author::ATTRIBUTE_FIRST_NAME,
            ],
            BaseQueryParser::PARAM_SORT => '-created,title,+updated',
            BaseQueryParser::PARAM_INCLUDE => Comment::LINK_AUTHOR . ',   ' .
                Comment::LINK_AUTHOR . '.' . Author::LINK_COMMENTS,
            BaseQueryParser::PARAM_PROFILE => \urlencode(\implode(' ', [$profileUrl1, $profileUrl2])),
        ];

        // It is expected that classes that encapsulate/extend BaseQueryParser would add features
        // such filters/pagination parsing, validation, etc. Though for simplicity we omit adding
        // them here and check how it integrates with EncodingParameters.
        $parser = new class($queryParameters) extends BaseQueryParser {
            private ?array $fields = null;

            private ?array $sorts = null;

            private ?array $includes = null;

            private ?array $profile = null;

            public function getFields(): array
            {
                if (null === $this->fields) {
                    $this->fields = $this->iterableToArray(parent::getFields());
                }

                return $this->fields;
            }

            public function getSorts(): array
            {
                if (null === $this->sorts) {
                    $this->sorts = $this->iterableToArray(parent::getSorts());
                }

                return $this->sorts;
            }

            public function getProfileUrls(): array
            {
                if (null === $this->profile) {
                    $this->profile = $this->iterableToArray(parent::getProfileUrls());
                }

                return $this->profile;
            }

            public function getIncludes(): array
            {
                if (null === $this->includes) {
                    $this->includes = \array_keys($this->iterableToArray(parent::getIncludes()));
                }

                return $this->includes;
            }

            private function iterableToArray(iterable $iterable): array
            {
                $result = [];

                foreach ($iterable as $key => $value) {
                    $result[$key] = $value instanceof \Generator ? $this->iterableToArray($value) : $value;
                }

                return $result;
            }
        };

        // Check parsing works fine
        self::assertSame(
            [
                'comments' => [Comment::LINK_AUTHOR, Comment::ATTRIBUTE_BODY],
                'people' => [Author::ATTRIBUTE_FIRST_NAME],
            ],
            $parser->getFields()
        );
        self::assertSame(
            [
                'created' => false,
                'title' => true,
                'updated' => true,
            ],
            $parser->getSorts()
        );
        self::assertSame(
            [
                Comment::LINK_AUTHOR,
                Comment::LINK_AUTHOR . '.' . Author::LINK_COMMENTS,
            ],
            $parser->getIncludes()
        );
        self::assertSame(
            [
                $profileUrl1,
                $profileUrl2,
            ],
            $parser->getProfileUrls()
        );

        //
        // Now the main purpose of the test. Will it work with EncodingParameters?
        //

        // firstly setup some data
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $comments = [
            Comment::instance(5, 'First!', $author),
            Comment::instance(12, 'I like XML better', $author),
        ];
        $author->{Author::LINK_COMMENTS} = $comments;

        // and encode with params taken from the parser
        $actual = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
                Comment::class => CommentSchema::class,
            ]
        )
            ->withIncludedPaths($parser->getIncludes())
            ->withFieldSets($parser->getFields())
            ->encodeData($comments);

        $expected = <<<EOL
        {
          "data": [
            {
              "type": "comments",
              "id": "5",
              "attributes": {
                "body": "First!"
              },
              "relationships": {
                "author": {
                  "links": {
                    "self"    : "/comments/5/relationships/author",
                    "related" : "/comments/5/author"
                  },
                  "data": { "type": "people", "id": "9" }
                }
              },
              "links": {
                "self": "/comments/5"
              }
            },
            {
              "type": "comments",
              "id": "12",
              "attributes": {
                "body": "I like XML better"
              },
              "relationships": {
                "author": {
                  "links": {
                    "self"    : "/comments/12/relationships/author",
                    "related" : "/comments/12/author"
                  },
                  "data": { "type": "people", "id": "9" }
                }
              },
              "links": {
                "self": "/comments/12"
              }
            }
          ],
          "included": [
            {
              "type": "people",
              "id": "9",
              "attributes":{
                "first_name":"Dan"
            },
            "links": {
                "self": "/people/9"
              }
            }
          ]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    private function createParser(array $queryParameters): BaseQueryParserInterface
    {
        return new BaseQueryParser($queryParameters);
    }

    private function iterableToArray(iterable $iterable): array
    {
        $result = [];

        foreach ($iterable as $key => $value) {
            $result[$key] = $value instanceof \Generator ? $this->iterableToArray($value) : $value;
        }

        return $result;
    }
}
