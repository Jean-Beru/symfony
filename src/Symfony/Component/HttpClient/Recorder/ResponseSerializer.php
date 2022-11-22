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
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ResponseSerializer implements ResponseSerializerInterface
{
    private VarCloner $varCloner;

    public function __construct()
    {
        if (!class_exists(VarCloner::class)) {
            throw new \LogicException(sprintf('Using "%s" requires that the VarCloner component version 4.4 or higher is installed, try running "composer require symfony/var-cloner:^6.2".', __CLASS__));
        }

        $this->varCloner = new VarCloner();
    }

    public function serialize(ResponseInterface $response): string
    {
        return serialize($this->varCloner->cloneVar([
            $response->getContent(false),
            $response->getInfo(),
        ]));
    }

    public function deserialize(string $data): ResponseInterface
    {
        $parts = unserialize($data, ['allowed_classes' => [Data::class]])->getValue(true);

        return new MockResponse($parts[0], $parts[1]);
    }

    private function serializeHeaders(array $headers): string
    {
        $parts = [];
        foreach ($headers as $name => $values) {
            $name = strtolower(trim($name));

            if ('set-cookie' === $name) {
                foreach ($values as $value) {
                    $parts[] = "{$name}:{$value}";
                }
            } else {
                $parts[] = sprintf('%s: %s', $name, implode(', ', $values));
            }
        }

        return implode(\PHP_EOL, $parts);
    }

    private function deserializeHeaders(string $data): array
    {
        if ('' === $data) {
            return [];
        }

        $headers = [];

        foreach (explode(\PHP_EOL, $data) as $row) {
            [$name, $values] = explode(': ', $row, 2);
            $name = strtolower(trim($name));

            if ('set-cookie' === $name) {
                $headers[$name][] = $values;
            } else {
                $headers[$name] = $values;
            }
        }

        return $headers;
    }
}
