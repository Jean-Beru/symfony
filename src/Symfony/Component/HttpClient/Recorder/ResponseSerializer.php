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

use Symfony\Component\HttpClient\Exception\TransportException;
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

    /**
     * @throws TransportException
     */
    public function deserialize(string $data): ResponseInterface
    {
        if (false === $data = @unserialize($data, ['allowed_classes' => [Data::class]])) {
            throw new TransportException('Unable to unserialize recorded response.');
        }

        $parts = $data->getValue(true);

        return new MockResponse($parts[0], $parts[1]);
    }
}
