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

use Symfony\Component\HttpClient\Recorder\CacheRecorder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Recorder\ResponseSerializerInterface;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\CacheInterface;

class CacheRecorderTest extends TestCase
{
    public function testRecord()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with('key');
        $cache->expects($this->once())->method('get')->with('key', $this->isType('callable'));

        $recorder = new CacheRecorder($cache, static fn() => 'key', $this->createMock(ResponseSerializerInterface::class));
        $recorder->record('GET', '/path', [], new MockResponse());
    }

    public function testReplay()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('get')->with('key', $this->isType('callable'))->willReturn('serialized');

        $serializer = $this->createMock(ResponseSerializerInterface::class);
        $serializer->expects($this->once())->method('deserialize')->with('serialized')->willReturn(new MockResponse('content'));

        $recorder = new CacheRecorder($cache, static fn() => 'key', $serializer);
        $response = $recorder->replay('GET', '/path', []);

        $this->assertSame('content', $response->getContent());
    }

    public function testReplayNotExists()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('get')->with('key', $this->isType('callable'))->willReturn(null);

        $serializer = $this->createMock(ResponseSerializerInterface::class);
        $serializer->expects($this->never())->method('deserialize');

        $recorder = new CacheRecorder($cache, static fn() => 'key', $serializer);
        $response = $recorder->replay('GET', '/path', []);

        $this->assertNull($response);
    }
}
