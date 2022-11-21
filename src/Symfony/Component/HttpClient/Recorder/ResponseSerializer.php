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
use Symfony\Contracts\HttpClient\ResponseInterface;

class ResponseSerializer implements ResponseSerializerInterface
{
    private const SEPARATOR = "\r\n\r\n";

    public function serialize(ResponseInterface $response): string
    {
        return implode(self::SEPARATOR, [
            $response->getStatusCode(),
            $this->serializeHeaders($response->getHeaders()),
            $response->getContent(),
        ]);
    }

    private function serializeHeaders(array $headers): string
    {
        $parts = [];
        foreach ($headers as $name => $values) {
            $name = strtolower(trim($name));

            if ('set-cookie' === $name) {
                foreach ($values as $value) {
                    $parts[] = "{$name}: {$value}";
                }
            } else {
                $parts[] = sprintf('%s: %s', $name, implode(', ', $values));
            }
        }

        return implode(\PHP_EOL, $parts);
    }

    public function deserialize(string $data): ResponseInterface
    {
        if (3 !== count($parts = explode(self::SEPARATOR, $data, 3))) {
            throw new RecordException(sprintf('%d part(s) found in record. Expected: 3.', count($parts)));
        }

        return new MockResponse($parts[2] ?? '', [
            'http_code' => (int) ($parts[0] ?? 200),
            'response_headers' => $this->deserializeHeaders($parts[1] ?? ''),
        ]);
    }

    private function deserializeHeaders(string $data): array
    {
        $headers = [];

        foreach (explode(\PHP_EOL, $data) as $row) {
            if (2 !== count($parts = explode(':', $row, 2))) {
                throw new RecordException(sprintf('Invalid record header "%s".', $row));
            }

            [$name, $values] = $parts;
            $name = strtolower(trim($name));

            if ('set-cookie' === $name) {
                $headers[$name][] = trim($values);
            } else {
                $headers[$name] = array_map('trim', explode(',', $values));
            }
        }

        return $headers;
    }
}
