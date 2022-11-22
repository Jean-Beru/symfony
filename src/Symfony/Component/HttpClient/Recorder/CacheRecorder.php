<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Recorder;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CacheRecorder implements RecorderInterface
{
    private CacheInterface $cache;
    private $keyGenerator;
    private ResponseSerializerInterface $responseSerializer;

    public function __construct(CacheInterface $cache, callable $keyGenerator, ResponseSerializerInterface $responseSerializer)
    {
        $this->cache = $cache;
        $this->keyGenerator = $keyGenerator;
        $this->responseSerializer = $responseSerializer;
    }

    public function record(string $method, string $url, array $options, ResponseInterface $response): void
    {
        $key = ($this->keyGenerator)($method, $url, $options);

        $this->cache->delete($key);
        $this->cache->get($key, fn() => $this->responseSerializer->serialize($response));
    }

    public function replay(string $method, string $url, array $options): ?ResponseInterface
    {
        $key = ($this->keyGenerator)($method, $url, $options);

        if (null === $serializedResponse = $this->cache->get($key, static fn() => null)) {
            return null;
        }

        return MockResponse::fromRequest($method, $url, $options, $this->responseSerializer->deserialize($serializedResponse));
    }
}
