<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit\Request;

use Pantheon\Terminus\Request\RequestOperationResult;
use Pantheon\Terminus\Tests\Unit\UnitTestCase;

/**
 * @covers \Pantheon\Terminus\Request\RequestOperationResult
 */
class RequestOperationResultTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetsAllProperties(): void
    {
        $data = ['key' => 'value'];
        $headers = ['Content-Type' => 'application/json'];
        $statusCode = 200;
        $statusCodeReason = 'OK';

        $result = new RequestOperationResult([
            'data' => $data,
            'headers' => $headers,
            'status_code' => $statusCode,
            'status_code_reason' => $statusCodeReason,
        ]);

        $this->assertEquals($data, $result->getData());
        $this->assertEquals($headers, $result->getHeaders());
        $this->assertEquals($statusCode, $result->getStatusCode());
        $this->assertEquals($statusCodeReason, $result->getStatusCodeReason());
    }

    /**
     * @test
     */
    public function isErrorReturnsTrueForNon2xxStatus(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 404,
            'status_code_reason' => 'Not Found',
        ]);

        $this->assertTrue($result->isError());
    }

    /**
     * @test
     */
    public function isErrorReturnsFalseFor2xxStatus(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $this->assertFalse($result->isError());
    }

    /**
     * @test
     */
    public function isErrorReturnsFalseFor201Status(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 201,
            'status_code_reason' => 'Created',
        ]);

        $this->assertFalse($result->isError());
    }

    /**
     * @test
     */
    public function isErrorReturnsTrueFor500Status(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 500,
            'status_code_reason' => 'Internal Server Error',
        ]);

        $this->assertTrue($result->isError());
    }

    /**
     * @test
     */
    public function isErrorReturnsTrueFor100Status(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 100,
            'status_code_reason' => 'Continue',
        ]);

        $this->assertTrue($result->isError());
    }

    /**
     * @test
     */
    public function toStringReturnsJsonForArrayData(): void
    {
        $data = ['key' => 'value', 'nested' => ['a' => 1]];
        $result = new RequestOperationResult([
            'data' => $data,
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $string = (string) $result;
        $decoded = json_decode($string, true);

        $this->assertEquals($data, $decoded);
    }

    /**
     * @test
     */
    public function toStringReturnsStringDataDirectly(): void
    {
        $data = 'plain text response';
        $result = new RequestOperationResult([
            'data' => $data,
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $this->assertEquals($data, (string) $result);
    }

    /**
     * @test
     */
    public function toStringReturnsEmptyStringForNullData(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $this->assertEquals('', (string) $result);
    }

    /**
     * @test
     */
    public function arrayAccessOffsetExistsWorks(): void
    {
        $result = new RequestOperationResult([
            'data' => ['test'],
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $this->assertTrue(isset($result['data']));
        $this->assertTrue(isset($result['headers']));
        $this->assertTrue(isset($result['status_code']));
        $this->assertFalse(isset($result['nonexistent']));
    }

    /**
     * @test
     */
    public function arrayAccessOffsetGetWorks(): void
    {
        $data = ['key' => 'value'];
        $result = new RequestOperationResult([
            'data' => $data,
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $this->assertEquals($data, $result['data']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertNull($result['nonexistent']);
    }

    /**
     * @test
     */
    public function settersWorkCorrectly(): void
    {
        $result = new RequestOperationResult([
            'data' => null,
            'headers' => [],
            'status_code' => 200,
            'status_code_reason' => 'OK',
        ]);

        $newData = ['new' => 'data'];
        $newHeaders = ['X-Custom' => 'header'];

        $result->setData($newData);
        $result->setHeaders($newHeaders);
        $result->setStatusCode(201);
        $result->setStatusCodeReason('Created');

        $this->assertEquals($newData, $result->getData());
        $this->assertEquals($newHeaders, $result->getHeaders());
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('Created', $result->getStatusCodeReason());
    }
}
