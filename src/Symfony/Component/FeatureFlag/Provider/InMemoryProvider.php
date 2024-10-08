<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\Provider;

use Symfony\Component\FeatureFlag\ArgumentResolver\ArgumentResolverInterface;

final class InMemoryProvider implements ProviderInterface
{
    /**
     * @param array<string, (\Closure(): mixed)> $features
     */
    public function __construct(
        private readonly array $features,
        private readonly ArgumentResolverInterface|null $argumentResolver = null,
    ) {
    }

    public function has(string $featureName): bool
    {
        return \array_key_exists($featureName, $this->features);
    }

    public function get(string $featureName): \Closure
    {
        $feature = $this->features[$featureName] ?? fn() => false;

        return function() use ($feature): mixed {
            $arguments = $this->argumentResolver?->getArguments($feature) ?? [];

            return $feature(...$arguments);
        };
    }

    public function getNames(): array
    {
        return array_keys($this->features);
    }
}
