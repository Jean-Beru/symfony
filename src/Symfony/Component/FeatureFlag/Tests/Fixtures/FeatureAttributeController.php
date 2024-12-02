<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\Tests\Fixtures;

use Symfony\Component\FeatureFlag\Attribute\Feature;

#[Feature(name: 'class')]
class FeatureAttributeController
{
    #[Feature(name: 'foo')]
    public function foo()
    {
    }

    #[Feature(name: 'bar', expected: 42)]
    public function bar()
    {
    }

    #[Feature(name: 'baz', message: 'Feature not available', statusCode: 403, exceptionCode: 42)]
    public function baz()
    {
    }
}
