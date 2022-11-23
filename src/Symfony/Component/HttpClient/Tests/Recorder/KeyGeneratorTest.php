<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Tests\Recorder;

use Symfony\Component\HttpClient\Recorder\KeyGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

class KeyGeneratorTest extends TestCase
{
    /**
     * @dataProvider provideGeneratedKey
     */
    public function testGeneratedKey(string $expected, string $method, string $url, array $options, string $prefix)
    {
        $this->assertSame($expected, (new KeyGenerator($prefix))($method, $url, $options));
    }

    public function provideGeneratedKey()
    {
        yield 'With a simple request' => ['GET--read', 'GET', '/read', [], ''];
        yield 'With a body' => ['POST--write-9dc580', 'POST', '/write', ['body' => '{"my":"payload}'], ''];
        yield 'With query parameters' => ['GET--read-396617', 'GET', '/read', ['query' => ['a']], ''];
        yield 'With different query parameters' => ['GET--read-4b1111', 'GET', '/read', ['query' => ['b']], ''];
        yield 'With headers' => ['GET--read-6ad5b8', 'GET', '/read', ['headers' => ['accept' => 'application/json']], ''];
        yield 'With a prefix' => ['api1:GET--read', 'GET', '/read', [], 'api1:'];
    }
}
