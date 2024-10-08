<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\ArgumentResolver;

use Symfony\Component\TypeInfo\Type;

interface ArgumentValueResolverInterface
{
    /**
     * @return array{0: mixed}
     */
    public function resolve(string $name, Type $type): array;
}
