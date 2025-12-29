<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Har;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/
 */
class HarFile
{
    /**
     * @param array{
     *     log: array{
     *         version: string,
     *         creator: array{name: string, version: string, comment: string},
     *         entries: list<array{
     *             startedDateTime: string,
     *             request: array{method: string, url: string, postData: array},
     *             response: array{status: int, headers: array, content: array},
     *         }>,
     *     },
     *  } $har
     */
    public function __construct(
        private readonly array $har,
    ) {
    }

    public static function create(): self
    {
        return new self([
            'log' => [
                'version' => '1.2',
                'creator' => [
                    'name' => 'Symfony HttpClient',
                ],
                'entries' => [],
            ],
        ]);
    }

    public static function createFromFile(string $harFile): self
    {
        if (!is_file($harFile)) {
            throw new \InvalidArgumentException(\sprintf('Invalid file path provided: "%s".', $harFile));
        }

        return new self(json_decode(file_get_contents($harFile), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function findEntry(string $method, string $url, array $options = []): ResponseInterface
    {
        foreach ($this->har['log']['entries'] as $entry) {
            ['response' => $response, 'request' => $request, 'startedDateTime' => $startedDateTime] = $entry;

            $body = $this->getContent($response['content']);
            $entryMethod = $request['method'];
            $entryUrl = $request['url'];
            $requestBody = $options['body'] ?? null;

            if ($method !== $entryMethod || $url !== $entryUrl) {
                continue;
            }

            if (null !== $requestBody && $requestBody !== $this->getContent($request['postData'] ?? [])) {
                continue;
            }

            $info = [
                'http_code' => $response['status'],
                'http_method' => $entryMethod,
                'response_headers' => [],
                'start_time' => strtotime($startedDateTime),
                'url' => $entryUrl,
            ];

            /** @var array{name: string, value: string} $header */
            foreach ($response['headers'] as $header) {
                ['name' => $name, 'value' => $value] = $header;

                $info['response_headers'][$name][] = $value;
            }

            return new MockResponse($body, $info);
        }

        throw new TransportException(\sprintf('HAR does not contain a response for HTTP request "%s" "%s".', $method, $url));
    }

    public function withEntry(ResponseInterface $response, string $method, string $url, array $options = []): self
    {
        // Extract request headers
        $requestHeaders = [];
        foreach ($options['headers'] ?? [] as $name => $value) {
            $requestHeaders[] = [
                'name' => $name,
                'value' => implode(', ', (array) $value),
            ];
        }

        // Extract request content
        $requestContent = $this->getPostData($options);
        $requestContentSize = match (true) {
            [] === $requestContent => 0,
            isset($requestContent['text']) => \strlen($requestContent['text']),
            default => -1,
        };

        // Extract response headers
        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = [
                    'name' => $name,
                    'value' => $value,
                ];
            }
        }

        // Extract response content
        $responseContent = [];
        if ($contentType = $response->getHeaders()['content-type'][0] ?? null) {
            $responseContent['mimeType'] = $contentType;
        }
        $responseContent['text'] = $response->getContent();
        $responseContent['size'] = \strlen($responseContent['text']);
        // If content is binary, encode it as base64
        if (preg_match('/[^\x20-\x7E\t\r\n]/', $responseContent['text'])) {
            $responseContent['text'] = base64_encode($responseContent['text']);
            $responseContent['encoding'] = 'base64';
        }

        // Extract response start time
        $info = $response->getInfo();
        [$s, $ms] = explode('.', $info['start_time'] ?? microtime(true));
        $startedDateTime = gmdate('Y-m-d\TH:i:s', $s).'.'.$ms.'Z';

        // Add entry
        $har = $this->har;
        $har['log']['entries'][] = [
            'startedDateTime' => $startedDateTime,
            'request' => [
                'method' => $method,
                'url' => $url,
                'queryString' => [],
                'httpVersion' => 'HTTP/1.1',
                'headers' => $requestHeaders,
                'headersSize' => -1,
                'cookies' => [],
                'postData' => $requestContent,
                'bodySize' => $requestContentSize,
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'statusText' => '',
                'httpVersion' => 'HTTP/1.1',
                'headers' => $responseHeaders,
                'headersSize' => -1,
                'cookies' => [],
                'redirectURL' => '',
                'content' => $responseContent,
                'bodySize' => $responseContent['size'],
            ]
        ];

        // Return new HAR
        return new self($har);
    }

    public function toArray(): array
    {
        return $this->har;
    }

    /**
     * @param array{text: string, encoding: string} $content
     */
    private function getContent(array $content): string
    {
        $text = $content['text'] ?? '';
        $encoding = $content['encoding'] ?? null;

        return match ($encoding) {
            'base64' => base64_decode($text),
            null => $text,
            default => throw new \InvalidArgumentException(\sprintf('Unsupported encoding "%s", currently only base64 is supported.', $encoding)),
        };
    }

    private function getPostData(array $options): array
    {
        if (!isset($options['body']) && !isset($options['json'])) {
            return [];
        }

        $postData = [];

        if (isset($options['json'])) {
            $postData['mimeType'] = 'application/json';
            $postData['text'] = json_encode($options['json'], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        } elseif (isset($options['body'])) {
            $postData['text'] = $options['body'];
            if (\is_string($postData['text'])) {
                $postData['mimeType'] = 'text/plain';
            } else {
                $postData['mimeType'] = 'application/octet-stream';
            }
        }

        return $postData;
    }
}
