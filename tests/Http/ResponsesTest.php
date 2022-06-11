<?php

declare(strict_types=1);

namespace Neomerx\Tests\JsonApi\Http;

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

use Mockery;
use Mockery\MockInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Http\BaseResponses;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Neomerx\JsonApi\Schema\Error;
use Neomerx\JsonApi\Schema\ErrorCollection;
use Neomerx\Tests\JsonApi\BaseTestCase;
use stdClass;

class ResponsesTest extends BaseTestCase
{
    private \Mockery\MockInterface $mock;

    private \Neomerx\JsonApi\Contracts\Http\ResponsesInterface $responses;

    /**
     * Set up tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock = Mockery::mock(BaseResponses::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->responses = $this->mock;
    }

    /**
     * Test code response has no content-type header.
     */
    public function test_code_response_has_no_content_type_header(): void
    {
        $expectedHeaders = [];
        $this->willBeCalledCreateResponse(null, 123, $expectedHeaders, 'some response');
        self::assertEquals('some response', $this->responses->getCodeResponse(123));
    }

    /**
     * Test response.
     */
    public function test_content_response1(): void
    {
        $data = new stdClass();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForData($data, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getContentResponse($data, 321));
    }

    /**
     * Test content response, with custom headers.
     */
    public function test_content_response2(): void
    {
        $data = new stdClass();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForData($data, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type', 'X-Custom' => 'Custom-Header'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals(
            'some response',
            $this->responses->getContentResponse(
                $data,
                321,
                [
                    'X-Custom' => 'Custom-Header',
                ]
            )
        );
    }

    /**
     * Test response.
     */
    public function test_created_response1(): void
    {
        $resource = new stdClass();
        $location = 'http://server.tld/resource-type/123';
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForData($resource, 'some json api');
        $headers = [
            BaseResponses::HEADER_CONTENT_TYPE => 'some/type',
            BaseResponses::HEADER_LOCATION => $location,
        ];
        $this->willBeCalledCreateResponse('some json api', BaseResponses::HTTP_CREATED, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getCreatedResponse($resource, $location));
    }

    /**
     * Test response, with custom headers.
     */
    public function test_created_response2(): void
    {
        $resource = new stdClass();
        $location = 'http://server.tld';
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForData($resource, 'some json api');
        $headers = [
            BaseResponses::HEADER_CONTENT_TYPE => 'some/type',
            BaseResponses::HEADER_LOCATION => $location,
            'X-Custom' => 'Custom-Header',
        ];
        $this->willBeCalledCreateResponse('some json api', BaseResponses::HTTP_CREATED, $headers, 'some response');
        self::assertEquals(
            'some response',
            $this->responses->getCreatedResponse(
                $resource,
                $location,
                [
                    'X-Custom' => 'Custom-Header',
                ]
            )
        );
    }

    /**
     * Test response.
     */
    public function test_meta_response1(): void
    {
        $meta = new stdClass();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForMeta($meta, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getMetaResponse($meta, 321));
    }

    /**
     * Test response, with custom headers.
     */
    public function test_meta_response2(): void
    {
        $meta = new stdClass();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForMeta($meta, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type', 'X-Custom' => 'Custom-Header'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals(
            'some response',
            $this->responses->getMetaResponse(
                $meta,
                321,
                [
                    'X-Custom' => 'Custom-Header',
                ]
            )
        );
    }

    /**
     * Test identifiers response.
     */
    public function test_identifiers_response1(): void
    {
        $data = new stdClass();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForIdentifiers($data, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getIdentifiersResponse($data, 321));
    }

    /**
     * Test identifiers response, with custom headers.
     */
    public function test_identifiers_response2(): void
    {
        $data = new stdClass();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForIdentifiers($data, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type', 'X-Custom' => 'Custom-Header'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals(
            'some response',
            $this->responses->getIdentifiersResponse(
                $data,
                321,
                [
                    'X-Custom' => 'Custom-Header',
                ]
            )
        );
    }

    /**
     * Test response.
     */
    public function test_error_response1(): void
    {
        $error = new Error();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForError($error, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getErrorResponse($error, 321));
    }

    /**
     * Test response.
     */
    public function test_error_response2(): void
    {
        $errors = [new Error()];
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForErrors($errors, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getErrorResponse($errors, 321));
    }

    /**
     * Test response.
     */
    public function test_error_response3(): void
    {
        $errors = new ErrorCollection();
        $errors->add(new Error());
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForErrors($errors, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals('some response', $this->responses->getErrorResponse($errors, 321));
    }

    /**
     * Test response, with custom headers.
     */
    public function test_error_response4(): void
    {
        $error = new Error();
        $this->willBeCalledGetMediaType('some', 'type');
        $this->willBeCalledEncoderForError($error, 'some json api');
        $headers = [BaseResponses::HEADER_CONTENT_TYPE => 'some/type', 'X-Custom' => 'Custom-Header'];
        $this->willBeCalledCreateResponse('some json api', 321, $headers, 'some response');
        self::assertEquals(
            'some response',
            $this->responses->getErrorResponse(
                $error,
                321,
                [
                    'X-Custom' => 'Custom-Header',
                ]
            )
        );
    }

    private function willBeCalledGetMediaType(string $type, string $subType, array $parameters = null): void
    {
        $mediaType = new MediaType($type, $subType, $parameters);

        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mock->shouldReceive('getMediaType')->once()->withNoArgs()->andReturn($mediaType);
    }

    /**
     * @param mixed $response
     */
    private function willBeCalledCreateResponse(?string $content, int $httpCode, array $headers, $response): void
    {
        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mock->shouldReceive('createResponse')->once()
            ->withArgs([$content, $httpCode, $headers])->andReturn($response);
    }

    private function willBeCalledGetEncoder(): MockInterface
    {
        $encoderMock = Mockery::mock(EncoderInterface::class);
        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $this->mock->shouldReceive('getEncoder')->once()->withNoArgs()->andReturn($encoderMock);

        return $encoderMock;
    }

    /**
     * @param mixed $data
     */
    private function willBeCalledEncoderForData($data, string $result): void
    {
        $encoderMock = $this->willBeCalledGetEncoder();

        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $encoderMock->shouldReceive('encodeData')
            ->once()
            ->withArgs([$data])
            ->andReturn($result);
    }

    /**
     * @param mixed $meta
     */
    private function willBeCalledEncoderForMeta($meta, string $result): void
    {
        $encoderMock = $this->willBeCalledGetEncoder();

        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $encoderMock->shouldReceive('encodeMeta')->once()->with($meta)->andReturn($result);
    }

    /**
     * @param mixed $data
     */
    private function willBeCalledEncoderForIdentifiers($data, string $result): void
    {
        $encoderMock = $this->willBeCalledGetEncoder();

        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $encoderMock->shouldReceive('encodeIdentifiers')
            ->once()
            ->withArgs([$data])
            ->andReturn($result);
    }

    /**
     * @param mixed $error
     */
    private function willBeCalledEncoderForError($error, string $result): void
    {
        $encoderMock = $this->willBeCalledGetEncoder();

        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $encoderMock->shouldReceive('encodeError')->once()->with($error)->andReturn($result);
    }

    private function willBeCalledEncoderForErrors(iterable $errors, string $result): void
    {
        $encoderMock = $this->willBeCalledGetEncoder();

        /* @noinspection PhpMethodParametersCountMismatchInspection */
        $encoderMock->shouldReceive('encodeErrors')->once()->with($errors)->andReturn($result);
    }
}
