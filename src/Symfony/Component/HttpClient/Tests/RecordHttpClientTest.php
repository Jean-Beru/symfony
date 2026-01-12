<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\RecordHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RecordHttpClientTest extends TestCase
{
    private const FIXTURES_FOLDER = __DIR__.'/Fixtures/recorder/';
    private string|null $archiveFile = null;

    protected function tearDown(): void
    {
        if ($this->archiveFile) {
            unlink($this->archiveFile);
            $this->archiveFile = null;
        }
    }

    public function testModeReplay()
    {
        $client = new RecordHttpClient(
            new MockHttpClient(),
            self::FIXTURES_FOLDER.'books.har',
            RecordHttpClient::MODE_REPLAY,
        );

        $response = $client->request('GET', 'https://example.com/api/books/1');
        $response = $this->getResponseForAssertion($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['id' => 1, 'name' => 'book name'], $response->toArray());

    }

    public function testModeReplayNotFound()
    {
        $this->expectException(TransportException::class);

        $client = new RecordHttpClient(
            new MockHttpClient(),
            self::FIXTURES_FOLDER.'books.har',
            RecordHttpClient::MODE_REPLAY,
        );

        $client->request('GET', '/not_found.json');
    }

    public function testModeRecord()
    {
        $originClient = new MockHttpClient(new MockResponse('My content', [
            'status_code' => 200,
            'start_time' => 1766570991.7282,
        ]));

        $this->archiveFile = tempnam(sys_get_temp_dir(), 'http_client_recorder_');

        $client = new RecordHttpClient(
            $originClient,
            $this->archiveFile,
            RecordHttpClient::MODE_RECORD,
        );
        $client->request('GET', 'https://example.com/example');

        $this->assertJsonFileEqualsJsonFile(self::FIXTURES_FOLDER.'expected_mode_record.har', $this->archiveFile);
    }

    private function getResponseForAssertion(ResponseInterface $response): ResponseInterface
    {
        return (new MockHttpClient($response))->request('GET', 'https://example.com/whatever');
    }
}
