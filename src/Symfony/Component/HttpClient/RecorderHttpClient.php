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
use Symfony\Component\HttpClient\Recorder\RecorderInterface;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Allows recording a request in a file and replaying it.
 *
 * @author Hubert Lenoir <lenoir.hubert@gmail.com>
 */
class RecorderHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait {
        __construct as __doConstruct;
    }

    public const MODE_RECORD = 'record';
    public const MODE_REPLAY = 'replay';

    private RecorderInterface $recorder;
    private string $mode;

    public function __construct(RecorderInterface $recorder, string $mode = self::MODE_REPLAY, HttpClientInterface $client = null)
    {
        $this->recorder = $recorder;
        $this->mode = $mode;
        $this->__doConstruct($client);
    }

    /**
     * @throws TransportException
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->mode === self::MODE_RECORD) {
            $response = $this->client->request($method, $url, $options);

            $this->recorder->record($method, $url, $options, $response);
        } else {
            $response = $this->recorder->replay($method, $url, $options);
            if (null === $response) {
                throw new TransportException(sprintf('Unable to replay response for %s request to %s.', $method, $url));
            }
        }

        return (new MockHttpClient($response))->request($method, $url, $options);
    }

    public function stream(ResponseInterface|iterable $responses, float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof ResponseInterface) {
            $responses = [$responses];
        }

        return new ResponseStream(MockResponse::stream($responses, $timeout));
    }
}
