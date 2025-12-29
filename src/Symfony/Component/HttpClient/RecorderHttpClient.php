<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Har\HarFile;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class RecorderHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    public const string MODE_REPLAY = 'replay';
    public const string MODE_RECORD_IF_MISSING = 'record_if_missing';
    public const string MODE_RECORD = 'record';
    private HarFile $harFile;

    public function __construct(
        private HttpClientInterface $client,
        private string $harPath,
        private readonly string $mode,
    ) {
        $this->setHarPath($this->harPath);
    }

    public function setHarPath(string $harPath): void
    {
        $this->harPath = $harPath;
        if (!file_exists($harPath) || !file_get_contents($harPath)) {
            $this->harFile = HarFile::create();
        } else {
            $this->harFile = HarFile::createFromFile($harPath);
        }
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (self::MODE_RECORD === $this->mode) {
            $response = $this->client->request($method, $url, $options);
            $this->harFile = $this->harFile->withEntry($response, $method, $url, $options);
            file_put_contents($this->harPath, json_encode($this->harFile->toArray(), \JSON_PRETTY_PRINT));
        }


        try {
            $response = $this->harFile->findEntry($method, $url, $options);
        } catch (TransportException $e) {
            if (self::MODE_RECORD_IF_MISSING !== $this->mode) {
                throw $e;
            }

            $response = $this->client->request($method, $url, $options);
            $this->harFile = $this->harFile->withEntry($response, $method, $url, $options);
            file_put_contents($this->harPath, json_encode($this->harFile->toArray(), \JSON_PRETTY_PRINT));
        }

        return $response;
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof ResponseInterface) {
            $responses = [$responses];
        }

        return new ResponseStream(MockResponse::stream($responses, $timeout));
    }
}
