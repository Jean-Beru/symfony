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

class KeyGenerator
{
    public function __invoke(string $method, string $url, array $options = []): string
    {
        $useHash = false;
        $ctx = hash_init('SHA512');
        $parts = [$method, $url];

        if ($body = ($options['body'] ?? null)) {
            hash_update($ctx, $body);
            $useHash = true;
        }

        if (!empty($options['query'])) {
            hash_update($ctx, http_build_query($options['query']));
            $useHash = true;
        }

        foreach ($options['headers'] ?? [] as $name => $value) {
            hash_update($ctx, sprintf('%s:%s', $name, $value));
            $useHash = true;
        }

        if ($useHash) {
            $parts[] = substr(hash_final($ctx), 0, 6);
        }

        return strtr(implode('-', $parts), ['://' => '-', '/' => '-']);
    }
}
