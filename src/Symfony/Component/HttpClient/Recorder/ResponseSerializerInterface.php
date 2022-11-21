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

use Symfony\Contracts\HttpClient\ResponseInterface;

interface ResponseSerializerInterface
{
    public function serialize(ResponseInterface $response): string;

    public function deserialize(string $data): ResponseInterface;
}
