<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Tests\Recorder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Recorder\ResponseSerializer;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ResponseSerializerTest extends TestCase
{
    public function testDeserializeException()
    {
        $this->expectException(TransportException::class);

        $serializer = new ResponseSerializer();
        $serializer->deserialize('not_a_record');
    }

    /**
     * @dataProvider provideSerializeAndDeserialize
     */
    public function testSerializeAndDeserialize(MockResponse $mock)
    {
        $serializer = new ResponseSerializer();

        $initial = $this->createReadableResponse($mock);
        $record = $serializer->deserialize($serializer->serialize($initial));
        $record = $this->createReadableResponse($record);

        $this->assertSame($initial->getStatusCode(), $record->getStatusCode());
        $this->assertSame($initial->getHeaders(false), $record->getHeaders(false));
        $this->assertSame($initial->getContent(false), $record->getContent(false));
        $this->assertSame($initial->getInfo('debug'), $record->getInfo('debug'));
    }

    public function provideSerializeAndDeserialize()
    {
        yield 'Empty response' => [
            new MockResponse(),
        ];

        yield 'Response with options' => [
            new MockResponse('My content', ['http_code' => 201, 'response_headers' => ['content-type' => 'application/json']]),
        ];

        yield 'Response with infos' => [
            new MockResponse('My content', ['http_code' => 201, 'response_headers' => ['content-type' => 'application/json'], 'debug' => 'dummy']),
        ];

        yield 'Response in error' => [
            new MockResponse('', ['http_code' => 500]),
        ];
    }

    private function createReadableResponse(MockResponse $mock): ResponseInterface
    {
        return (new MockHttpClient($mock))->request('GET', '');
    }
}
