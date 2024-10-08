<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\FeatureFlag\FeatureCheckerInterface;
use Symfony\Component\FeatureFlag\Tests\Fixtures\FeatureAttributeController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FeatureAttributeListenerTest extends TestCase
{
    /**
     * @dataProvider provideAttribute
     */
    public function testAttribute(string $method, array $map, bool $isGranted)
    {
        if ($isGranted) {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(NotFoundHttpException::class);
        }

        $featureChecker = $this->createStub(FeatureCheckerInterface::class);
        $featureChecker->method('isEnabled')->willReturnCallback(fn (string $featureName) => $map[$featureName] ?? false);

        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            [new FeatureAttributeController(), $method],
            [],
            new Request(),
            null
        );

        $listener = new FeatureAttributeListener($featureChecker);
        $listener->onControllerArguments($event);
    }

    public static function provideAttribute()
    {
        yield 'Granted' => ['foo', ['class' => true, 'foo' => true], true];
        yield 'Granted with a valid value' => ['bar', ['class' => true, 'bar' => true], true];
        yield 'Denied due to a missing feature on class' => ['foo', ['foo' => true], false];
        yield 'Denied due to a missing feature on method' => ['foo', ['class' => true], false];
        yield 'Denied due to an invalid value' => ['bar', ['class' => true, 'bar' => false], false];
    }

    public function testAttributeWithCustomException()
    {
        $this->expectExceptionObject(new HttpException(403, 'Feature not available', null, [], 42));

        $featureChecker = $this->createStub(FeatureCheckerInterface::class);
        $featureChecker->method('isEnabled')->willReturnCallback(fn (string $featureName) => 'class' === $featureName);

        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            [new FeatureAttributeController(), 'baz'],
            [],
            new Request(),
            null
        );

        $listener = new FeatureAttributeListener($featureChecker);
        $listener->onControllerArguments($event);
    }
}
