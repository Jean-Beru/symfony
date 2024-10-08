<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Symfony\Component\FeatureFlag\ArgumentResolver\ArgumentResolverInterface;
use Symfony\Component\FeatureFlag\Provider\InMemoryProvider;

class InMemoryProviderTests extends TestCase
{
    public function testHas()
    {
        $provider = new InMemoryProvider([
            'feature' => fn () => true,
        ]);

        $this->assertTrue($provider->has('feature'));
        $this->assertFalse($provider->has('unknown'));
    }

    public function testGet()
    {
        $provider = new InMemoryProvider([
            'feature' => fn () => true,
        ]);

        $feature = $provider->get('feature');

        $this->assertIsCallable($feature);
        $this->assertTrue($feature());
    }

    public function testGetLazy()
    {
        $provider = new InMemoryProvider([
            'exception' => fn () => throw new \LogicException('Should not be called.'),
        ]);

        $this->assertIsCallable($provider->get('exception'));
    }

    public function testGetNotFound()
    {
        $provider = new InMemoryProvider([]);

        $feature = $provider->get('unknown');

        $this->assertIsCallable($feature);
        $this->assertFalse($feature());
    }

    public function testGetNames()
    {
        $provider = new InMemoryProvider([
            'first' => fn () => true,
            'second' => fn () => false,
        ]);

        $this->assertSame(['first', 'second'], $provider->getNames());
    }

    public function testGetWithArgumentResolver()
    {
        $argumentResolver = $this->createConfiguredMock(ArgumentResolverInterface::class, [
            'getArguments' => [42, 'value'],
        ]);
        $provider = new InMemoryProvider(
            [
                'feature' => fn (int $a, string $b) => $a.$b,
            ],
            $argumentResolver,
        );

        $this->assertSame('42value', $provider->get('feature')());
    }
}
