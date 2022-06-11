<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Encoder;

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

use ArrayIterator;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Schema\Link;
use Neomerx\JsonApi\Schema\LinkWithAliases;
use Neomerx\Tests\JsonApi\BaseTestCase;
use Neomerx\Tests\JsonApi\Data\Collection;
use Neomerx\Tests\JsonApi\Data\Models\Author;
use Neomerx\Tests\JsonApi\Data\Models\AuthorCModel;
use Neomerx\Tests\JsonApi\Data\Models\AuthorIdentity;
use Neomerx\Tests\JsonApi\Data\Models\Comment;
use Neomerx\Tests\JsonApi\Data\Models\CommentIdentity;
use Neomerx\Tests\JsonApi\Data\Models\Site;
use Neomerx\Tests\JsonApi\Data\Schemas\AuthorCModelSchema;
use Neomerx\Tests\JsonApi\Data\Schemas\AuthorSchema;
use Neomerx\Tests\JsonApi\Data\Schemas\CommentSchema;
use Neomerx\Tests\JsonApi\Data\Schemas\SiteSchema;

class EncodeSimpleObjectsTest extends BaseTestCase
{
    /**
     * Test encode null.
     */
    public function test_encode_null(): void
    {
        $encoder = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
            ]
        );

        $actual = $encoder->encodeData(null);

        $expected = <<<EOL
        {
            "data" : null
        }
EOL;

        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode empty array.
     */
    public function test_encode_empty(): void
    {
        $encoder = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
            ]
        );

        $actual = $encoder->encodeData([]);

        $expected = <<<EOL
        {
            "data" : []
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode empty iterator.
     */
    public function test_encode_empty_iterator(): void
    {
        $encoder = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
            ]
        );

        $actual = $encoder->encodeData(new ArrayIterator([]));

        $expected = <<<EOL
        {
            "data": []
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode empty array.
     *
     * Issue #50 @see https://github.com/neomerx/json-api/issues/50
     */
    public function test_encode_empty_with_parameters(): void
    {
        $encoder = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
            ]
        )->withFieldSets(
            [
                // include only these attributes and links
                'authors' => [Author::ATTRIBUTE_FIRST_NAME, Author::LINK_COMMENTS],
            ]
        );

        $actual = $encoder->encodeData([]);

        $expected = <<<EOL
        {
            "data" : []
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object with attributes only.
     */
    public function test_encode_object_with_attributes_only(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData($author);

        $expected = <<<EOL
        {
            "data" : {
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode identifier.
     */
    public function test_encode_identifier(): void
    {
        $encoder = Encoder::instance([]);

        $identity = (new AuthorIdentity('123'))->setIdentifierMeta('id meta');

        $actual = $encoder->encodeData($identity);
        $expected = <<<EOL
        {
            "data" : {
                "type" : "people",
                "id"   : "123",
                "meta" : "id meta"
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);

        // same but as an array

        $actual = $encoder->encodeData([$identity]);
        $expected = <<<EOL
        {
            "data" : [{
                "type" : "people",
                "id"   : "123",
                "meta" : "id meta"
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object without ID and attributes only.
     */
    public function test_encode_object_with_attributes_only_and_no_id(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $author->{Author::ATTRIBUTE_ID} = null;
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);
                    $schema->setResourceLinksClosure(
                        function () {
                            return []; // no `self` link and others
                        }
                    );

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData($author);

        $expected = <<<EOL
        {
            "data" : {
                "type"       : "people",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                }
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object with attributes and custom links.
     *
     * @see https://github.com/neomerx/json-api/issues/64
     */
    public function test_encode_object_with_attributes_and_custom_links(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt')->setResourceMeta('resource meta');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);
                    $schema->setResourceLinksClosure(
                        function ($resource) {
                            self::assertNotNull($resource);

                            return [
                                'custom' => new Link(false, 'http://custom-link.com/', false),
                            ];
                        }
                    );

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData($author);

        $expected = <<<EOL
        {
            "data" : {
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "custom" : "http://custom-link.com/"
                },
                "meta": "resource meta"
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object as resource identity.
     */
    public function test_encode_object_as_resource_identity(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt')->setIdentifierMeta('id meta');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeIdentifiers($author);

        $expected = <<<EOL
        {
            "data" : {
                "type" : "people",
                "id"   : "9",
                "meta": "id meta"
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode array of simple objects as resource identity.
     */
    public function test_encode_array_as_resource_identity(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeIdentifiers([$author]);

        $expected = <<<EOL
        {
            "data" : [{
                "type" : "people",
                "id"   : "9"
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object as resource identity with included resources.
     */
    public function test_encode_object_as_resource_identity_with_includes(): void
    {
        $comment = Comment::instance(1, 'One!');
        $author = Author::instance(9, 'Dan', 'Gebhardt', [$comment]);
        $comment->{Comment::LINK_AUTHOR} = $author;

        $actual = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
                Comment::class => CommentSchema::class,
            ]
        )
            ->withUrlPrefix('http://example.com')
            ->withIncludedPaths([Comment::LINK_AUTHOR])
            ->encodeIdentifiers($comment);

        $expected = <<<EOL
        {
            "data" : {
                "type" : "comments",
                "id"   : "1"
            },
            "included": [
                {
                    "type" : "comments",
                    "id"   : "1",
                    "attributes": {
                        "body": "One!"
                    },
                    "relationships": {
                        "author": {
                            "links": {
                                "self"    : "http://example.com/comments/1/relationships/author",
                                "related" : "http://example.com/comments/1/author"
                            },
                            "data": {
                                "type" : "people",
                                "id"   : "9"
                            }
                        }
                    },
                    "links": {
                        "self": "http://example.com/comments/1"
                    }
                }, {
                    "type" : "people",
                    "id"   : "9",
                    "attributes": {
                        "first_name" : "Dan",
                        "last_name"  : "Gebhardt"
                    },
                    "relationships": {
                        "comments": {
                            "links": {
                                "self"    : "http://example.com/people/9/relationships/comments",
                                "related" : "http://example.com/people/9/comments"
                            },
                            "data": [
                                { "type" : "comments", "id"   : "1" }
                            ]
                        }
                    },
                    "links": {
                        "self": "http://example.com/people/9"
                    }
                }
            ]
        }
EOL;

        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode plain identifiers.
     */
    public function test_encode_identifiers(): void
    {
        $author = new AuthorIdentity('123');
        $encoder = Encoder::instance([]);

        $actual = $encoder->encodeIdentifiers($author);
        $expected = <<<EOL
        {
            "data" : {
                "type" : "people",
                "id"   : "123"
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);

        // same as array

        $actual = $encoder->encodeIdentifiers([$author]);
        $expected = <<<EOL
        {
            "data" : [{
                "type" : "people",
                "id"   : "123"
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode plain identifiers.
     */
    public function test_encode_null_identifier(): void
    {
        $encoder = Encoder::instance([]);

        $actual = $encoder->encodeIdentifiers(null);
        $expected = <<<EOL
        {
            "data" : null
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object with attributes only in array.
     */
    public function test_encode_object_with_attributes_only_in_array(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData([$author]);

        $expected = <<<EOL
        {
            "data" : [{
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object with attributes only associative array.
     */
    public function test_encode_object_with_attributes_only_in_assoc_array(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData(['key_does_not_matter' => $author]);

        $expected = <<<EOL
        {
            "data" : [{
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object in pretty format.
     */
    public function test_encode_object_with_attributes_only_pretty_printed(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com')->withEncodeOptions(JSON_PRETTY_PRINT);

        $actual = $encoder->encodeData($author);

        $expected = <<<EOL
{
    "data": {
        "type": "people",
        "id": "9",
        "attributes": {
            "first_name": "Dan",
            "last_name": "Gebhardt"
        },
        "links": {
            "self": "http:\/\/example.com\/people\/9"
        }
    }
}
EOL;

        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode array of simple objects with attributes only.
     */
    public function test_encode_array_of_objects_with_attributes_only(): void
    {
        $author1 = Author::instance(7, 'First', 'Last');
        $author2 = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData([$author1, $author2]);

        $expected = <<<EOL
        {
            "data" : [
                {
                    "type"       : "people",
                    "id"         : "7",
                    "attributes" : {
                        "first_name" : "First",
                        "last_name"  : "Last"
                    },
                    "links" : {
                        "self" : "http://example.com/people/7"
                    }
                },
                {
                    "type"       : "people",
                    "id"         : "9",
                    "attributes" : {
                        "first_name" : "Dan",
                        "last_name"  : "Gebhardt"
                    },
                    "links" : {
                        "self" : "http://example.com/people/9"
                    }
                }
            ]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode meta and top-level links for simple object.
     */
    public function test_encode_meta_andtop_links_for_simple_object(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $links = [Link::SELF => new Link(true, '/people/9', false)];
        $profile = [
            new LinkWithAliases(false, 'http://example.com/profiles/flexible-pagination', [], false),
            new LinkWithAliases(false, 'http://example.com/profiles/resource-versioning', ['version' => 'v'], false),
        ];
        $meta = [
            'copyright' => 'Copyright 2015 Example Corp.',
            'authors' => [
                'Yehuda Katz',
                'Steve Klabnik',
                'Dan Gebhardt',
            ],
        ];

        $actual = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )
            ->withUrlPrefix('http://example.com')
            ->withLinks([])
            ->withLinks($links)
            ->withProfile($profile)
            ->withMeta($meta)
            ->encodeData($author);

        $expected = <<<EOL
        {
            "meta" : {
                "copyright" : "Copyright 2015 Example Corp.",
                "authors" : [
                    "Yehuda Katz",
                    "Steve Klabnik",
                    "Dan Gebhardt"
                ]
            },
            "links" : {
                "self" : "http://example.com/people/9",
                "profile": [
                    "http://example.com/profiles/flexible-pagination",
                    {
                        "href"    : "http://example.com/profiles/resource-versioning",
                        "aliases" : {
                            "version": "v"
                        }
                    }
                ]
            },
            "data" : {
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode meta.
     */
    public function test_encode_meta(): void
    {
        $meta = [
            'copyright' => 'Copyright 2015 Example Corp.',
            'authors' => [
                'Yehuda Katz',
                'Steve Klabnik',
                'Dan Gebhardt',
            ],
        ];

        $actual = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->encodeMeta($meta);

        $expected = <<<EOL
        {
            "meta" : {
                "copyright" : "Copyright 2015 Example Corp.",
                "authors" : [
                    "Yehuda Katz",
                    "Steve Klabnik",
                    "Dan Gebhardt"
                ]
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encoding with JSON API version.
     */
    public function test_encode_json_api_version(): void
    {
        $actual = Encoder::instance()
            ->withJsonApiVersion(Encoder::JSON_API_VERSION)->withJsonApiMeta(['some' => 'meta'])
            ->encodeData(null);

        $expected = <<<EOL
        {
            "jsonapi" : {
                "version" : "1.1",
                "meta"    : { "some" : "meta" }
            },
            "data" : null
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode polymorphic array (resources of different types).
     */
    public function test_encode_polymorphic_array(): void
    {
        $author = Author::instance(7, 'First', 'Last', []);
        $site = Site::instance(9, 'Main Site', []);
        $encoder = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
                Site::class => SiteSchema::class,
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData([$author, $site]);

        $expected = <<<EOL
        {
            "data" : [
                {
                    "type" : "people",
                    "id"   : "7",
                    "attributes" : {
                        "first_name" : "First",
                        "last_name"  : "Last"
                    },
                    "relationships" : {
                        "comments" : {
                            "links": {
                                "self"    : "http://example.com/people/7/relationships/comments",
                                "related" : "http://example.com/people/7/comments"
                            },
                            "data" : []
                        }
                    },
                    "links" : {
                        "self":"http://example.com/people/7"
                    }
                }, {
                    "type" : "sites",
                    "id"   : "9",
                    "attributes" : {
                        "name" : "Main Site"
                    },
                    "relationships" : {
                        "posts" : {
                            "links": {
                                "self"    : "http://example.com/sites/9/relationships/posts",
                                "related" : "http://example.com/sites/9/posts"
                            },
                            "data" : []
                        }
                    },
                    "links" : {
                        "self":"http://example.com/sites/9"
                    }
                }
            ]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode simple object with attributes only in ArrayAccess collection.
     */
    public function test_encode_object_with_attributes_only_in_array_access_collection(): void
    {
        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => function ($factory) {
                    $schema = new AuthorSchema($factory);
                    $schema->removeRelationship(Author::LINK_COMMENTS);

                    return $schema;
                },
            ]
        )->withUrlPrefix('http://example.com');

        $collection = new Collection();
        $collection[] = $author;

        $actual = $encoder->encodeData($collection);

        $expected = <<<EOL
        {
            "data" : [{
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode with Schema instance.
     *
     * @see https://github.com/neomerx/json-api/issues/168
     */
    public function test_encode_with_schma_instance(): void
    {
        $authorSchema = new AuthorSchema(new Factory());
        $authorSchema->removeRelationship(Author::LINK_COMMENTS);

        $author = Author::instance(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                Author::class => $authorSchema,
            ]
        )->withUrlPrefix('http://example.com');

        $actual = $encoder->encodeData($author);

        $expected = <<<EOL
        {
            "data" : {
                "type"       : "people",
                "id"         : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode for 2 relationships that have identical name beginnings.
     *
     * @see https://github.com/neomerx/json-api/issues/200
     */
    public function test_encoding_similar_relationships(): void
    {
        /**
         * It's odd to have a second comments relationship and the naming is also weird but...
         * let's check a relationship that which naming start identical to the first one.
         */
        $secondRelName = Author::LINK_COMMENTS . '-second-name';

        $comment1 = Comment::instance(1, 'One!');
        $comment5 = Comment::instance(5, 'Five!');
        $author = Author::instance(9, 'Dan', 'Gebhardt', [$comment1]);

        $actual = Encoder::instance(
            [
                Author::class => function ($factory) use ($secondRelName, $comment5) {
                    $schema = new AuthorSchema($factory);

                    // make the author have the comment only in that odd relationship
                    // we will emulate the new relationship with that comment
                    $schema->addToRelationship(
                        $secondRelName,
                        AuthorSchema::RELATIONSHIP_DATA,
                        fn () => [$comment5]
                    );

                    // hide links
                    $schema->hideDefaultLinksInRelationship(Author::LINK_COMMENTS);
                    $schema->hideDefaultLinksInRelationship($secondRelName);

                    return $schema;
                },
                Comment::class => CommentSchema::class,
            ]
        )->withUrlPrefix('http://example.com')->withIncludedPaths(
            // include only the new odd relationship and omit the original `comments` relationship
            [$secondRelName]
        )->encodeData($author);

        // The issue was that comment with id 1 was also added in `included` section
        $expected = <<<EOL
        {
            "data": {
                "type": "people",
                "id": "9",
                "attributes": {
                    "first_name": "Dan",
                    "last_name": "Gebhardt"
                },
                "relationships": {
                    "comments": {
                    "data": [
                        {
                            "type": "comments",
                            "id": "1"
                        }
                    ]
                    },
                    "comments-second-name": {
                        "data": [
                            {
                                "type": "comments",
                                "id": "5"
                            }
                        ]
                    }
                },
                "links": {
                    "self": "http://example.com/people/9"
                }
            },
            "included": [
                {
                    "type": "comments",
                    "id": "5",
                    "attributes": {
                    "body": "Five!"
                    },
                    "relationships": {
                        "author": {
                            "links": {
                                "self"    : "http://example.com/comments/5/relationships/author",
                                "related" : "http://example.com/comments/5/author"
                            },
                            "data": null
                        }
                    },
                    "links": {
                        "self": "http://example.com/comments/5"
                    }
                }
            ]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode array-based objects.
     *
     * @see https://github.com/neomerx/json-api/pull/214
     */
    public function test_encode_array_based_object(): void
    {
        $author = new AuthorCModel(9, 'Dan', 'Gebhardt');
        $encoder = Encoder::instance(
            [
                AuthorCModel::class => AuthorCModelSchema::class,
            ]
        );

        $actual = $encoder->withUrlPrefix('http://example.com')->encodeData($author);

        $expected = <<<EOL
        {
            "data" : {
                "type" : "people",
                "id"   : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "relationships" : {
                    "comments" : {
                        "links": {
                            "self"    : "http://example.com/people/9/relationships/comments",
                            "related" : "http://example.com/people/9/comments"
                        }
                    }
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);

        // same but as array

        $actual = $encoder->withUrlPrefix('http://example.com')->encodeData([$author]);

        $expected = <<<EOL
        {
            "data" : [{
                "type" : "people",
                "id"   : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "relationships" : {
                    "comments" : {
                        "links": {
                            "self"    : "http://example.com/people/9/relationships/comments",
                            "related" : "http://example.com/people/9/comments"
                        }
                    }
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }]
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * Test encode resource with an array of identifiers in a relationship.
     *
     * @see https://github.com/neomerx/json-api/pull/235
     */
    public function test_encoding_identifier_arrays(): void
    {
        $commentId1 = new CommentIdentity('1');
        $commentId2 = new CommentIdentity('2');
        $author = Author::instance(9, 'Dan', 'Gebhardt', [$commentId1, $commentId2]);

        $actual = Encoder::instance(
            [
                Author::class => AuthorSchema::class,
                Comment::class => CommentSchema::class,
            ]
        )->withUrlPrefix('http://example.com')->withIncludedPaths([Author::LINK_COMMENTS])->encodeData($author);

        // The issue was that comment with id 1 was also added in `included` section
        $expected = <<<EOL
        {
            "data": {
                "type" : "people",
                "id"   : "9",
                "attributes" : {
                    "first_name" : "Dan",
                    "last_name"  : "Gebhardt"
                },
                "relationships" : {
                    "comments" : {
                        "data": [
                            { "type" : "comments", "id" : "1" },
                            { "type" : "comments", "id" : "2" }
                        ],
                        "links" : {
                            "self"    : "http://example.com/people/9/relationships/comments",
                            "related" : "http://example.com/people/9/comments"
                        }
                    }
                },
                "links" : {
                    "self" : "http://example.com/people/9"
                }
            }
        }
EOL;
        self::assertJsonStringEqualsJsonString($expected, $actual);
    }
}
