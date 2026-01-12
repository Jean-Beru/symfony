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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Har\HarFile;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class RecordHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    public const string MODE_REPLAY = 'replay';
    public const string MODE_RECORD_IF_MISSING = 'record_if_missing';
    public const string MODE_RECORD = 'record';

    private static string $harFilename = 'default';


    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $harFolder,
        private readonly string $mode,
    ) {
    }

    public static function setHarFilename(string $filename): void
    {
        static::$harFilename = $filename;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $harPath = $this->harFolder.'/'.static::$harFilename;

        if (file_exists($harPath) && file_get_contents($harPath)) {
            $harFile = HarFile::createFromFile($harPath);
        } else {
            $harFile = HarFile::create();
        }

        if (self::MODE_RECORD === $this->mode) {
            $response = $this->client->request($method, $url, $options);
            $harFile = $harFile->withEntry($response, $method, $url, $options);
            (new Filesystem())->dumpFile($harPath, json_encode($harFile->toArray(), \JSON_PRETTY_PRINT));
        }

        try {
            $response = (new MockHttpClient($harFile->findEntry($method, $url, $options)))->request($method, $url, $options);
        } catch (TransportException $e) {
            if (self::MODE_RECORD_IF_MISSING !== $this->mode) {
                throw $e;
            }

            $response = $this->client->request($method, $url, $options);
            $harFile = $harFile->withEntry($response, $method, $url, $options);
            (new Filesystem())->dumpFile($harPath, json_encode($harFile->toArray(), \JSON_PRETTY_PRINT));
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
