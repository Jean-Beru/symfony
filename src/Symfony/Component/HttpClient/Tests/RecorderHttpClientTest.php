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
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\HttpClient\Recorder\RecorderInterface;
use Symfony\Component\HttpClient\RecorderHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecorderHttpClientTest extends TestCase
{
    public function testRecord()
    {
        $recorder = $this->createMock(RecorderInterface::class);
        $recorder->expects($this->once())->method('record');
        $recorder->expects($this->never())->method('replay');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())->method('request');

        $recorderClient = new RecorderHttpClient($recorder, 'record', $client);
        $recorderClient->request('GET', '/');
    }

    public function testReplay()
    {
        $recorder = $this->createMock(RecorderInterface::class);
        $recorder->expects($this->never())->method('record');
        $recorder->expects($this->once())->method('replay')->willReturn(new MockResponse());

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->never())->method('request');

        $recorderClient = new RecorderHttpClient($recorder, 'replay', $client);
        $recorderClient->request('GET', '/');
    }

    public function testReplayWithoutResponse()
    {
        $this->expectExceptionObject(new TransportException('Unable to replay response for GET request to /.'));

        $recorder = $this->createMock(RecorderInterface::class);
        $recorder->expects($this->never())->method('record');
        $recorder->expects($this->once())->method('replay')->willReturn(null);

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->never())->method('request');

        $recorderClient = new RecorderHttpClient($recorder, 'replay', $client);
        $recorderClient->request('GET', '/');
    }
}
