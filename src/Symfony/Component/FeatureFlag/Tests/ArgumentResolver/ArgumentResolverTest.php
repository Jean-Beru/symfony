<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\Tests\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\FeatureFlag\ArgumentResolver\ArgumentResolver;
use Symfony\Component\FeatureFlag\ArgumentResolver\ArgumentValueResolverInterface;
use Symfony\Component\FeatureFlag\Exception\RuntimeException;
use Symfony\Component\TypeInfo\Type;

class ArgumentResolverTest extends TestCase
{
    private ArgumentResolver $argumentResolver;

    protected function setUp(): void
    {
        $this->argumentResolver = new ArgumentResolver([
            new class implements ArgumentValueResolverInterface {
                public function resolve(string $name, Type $type): array
                {
                    return match((string) $type->getBaseType()) {
                        'int' => [42],
                        'string' => ['value'],
                        default => [],
                    };
                }
            }
        ]);
    }

    public function testResolve()
    {
        $this->assertSame(['a' => 42, 'b' => 'value'], $this->argumentResolver->getArguments(fn(int $a, string $b) => true));
    }

    public function testResolveException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve value for parameter "c"');

        $this->argumentResolver->getArguments(fn(int $a, string $b, float $c) => true);
    }
}
