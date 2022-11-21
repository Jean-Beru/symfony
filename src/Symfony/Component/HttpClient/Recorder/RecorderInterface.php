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

interface RecorderInterface
{
    public function record(string $method, string $url, array $options, ResponseInterface $response): void;

    public function replay(string $method, string $url, array $options): ?ResponseInterface;
}
