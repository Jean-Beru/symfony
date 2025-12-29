<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Tests\Har;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Har\HarFile;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Test\HarFileReader;
use Symfony\Component\HttpClient\Test\HarFileWriter;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HarFileTest extends TestCase
{
    private const string FIXTURES_FOLDER = __DIR__.'/../Fixtures/har/';

    public function testFindEntryByMethodAndUrl()
    {
        $harFile = HarFile::createFromFile(self::FIXTURES_FOLDER.'/books.har');

        $response = $harFile->findEntry('GET', 'https://example.com/api/books/1');
        $response = $this->getResponseForAssertion($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['id' => 1, 'name' => 'book name'], $response->toArray());
    }

    public function testFindEntryWithBodyMatching()
    {
        $harFile = HarFile::createFromFile(self::FIXTURES_FOLDER.'/books.har');

        $response = $harFile->findEntry('POST', 'https://example.com/comment', [
            'body' => 'comment=Hello!',
        ]);
        $response = $this->getResponseForAssertion($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Comment sent', $response->getContent());
    }


    public function testFindEntryWithJsonMatching()
    {
        $harFile = HarFile::createFromFile(self::FIXTURES_FOLDER.'/books.har');

        $response = $harFile->findEntry('POST', 'https://example.com/api/books', [
            'json' => ['name' => 'book name'],
        ]);
        $response = $this->getResponseForAssertion($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(['id' => 2, 'name' => 'new book name'], $response->toArray());
    }

    public function testFindEntryNotFound()
    {
        $this->expectException(TransportException::class);

        $reader = HarFile::createFromFile(self::FIXTURES_FOLDER.'/books.har');
        $reader->findEntry('GET', 'https://example.com/api/books/42');
    }

    public function testFileNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reader = HarFile::createFromFile('/invalid/file.har');
        $reader->findEntry('GET', 'https://example.com/whatever');
    }

    public function testBinaryContentHandling()
    {
        $this->markTestSkipped('TODO');
    }

    public function testWithEntry()
    {
        $response = new MockResponse('Test content', [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'text/plain',
                'x-custom' => 'value',
            ],
            'start_time' => 1671974400.1234,
        ]);
        $response = $this->getResponseForAssertion($response);

        $options = [
            'headers' => [
                'Accept' => 'text/plain',
            ],
        ];

        $harFile = HarFile::create();
        $harFile = $harFile->withEntry($response,'GET', 'https://example.com/test', $options);

        $this->assertJsonStringEqualsJsonFile(self::FIXTURES_FOLDER.'/expected.har', json_encode($harFile->toArray()));
    }

    private function getResponseForAssertion(ResponseInterface $response): ResponseInterface
    {
        return (new MockHttpClient($response))->request('GET', 'https://example.com/whatever');
    }
}
