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

use Symfony\Component\FeatureFlag\Exception\RuntimeException;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * Resolves arguments by looping over registered resolvers.
 */
final class ArgumentResolver implements ArgumentResolverInterface
{
    /**
     * @param iterable<ArgumentValueResolverInterface> $resolvers
     */
    public function __construct(
        private readonly iterable $resolvers,
    ) {
    }

    /**
     * @throws RuntimeException when an argument cannot be resolved
     *
     */
    public function getArguments(\Closure $closure): array
    {
        $arguments = [];

        $typeResolver = TypeResolver::create();
        $reflValueResolver = new \ReflectionFunction($closure);
        foreach ($reflValueResolver->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $typeResolver->resolve($parameter);
            foreach ($this->resolvers as $resolver) {
                $value = $resolver->resolve($name, $type);
                if ([] !== $value) {
                    $arguments[$name] = $value[0];
                    continue 2;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(\sprintf('Unable to resolve value for parameter "%s".', $parameter->getName()));
        }

        return $arguments;
    }
}
