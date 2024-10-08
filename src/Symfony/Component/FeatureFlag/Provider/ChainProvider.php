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

class ChainProvider implements ProviderInterface
{
    public function __construct(
        /** @var list<ProviderInterface> */
        private readonly iterable $providers = [],
    ) {
    }

    public function has(string $featureName): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($featureName)) {
                return true;
            }
        }

        return false;
    }

    public function get(string $featureName): \Closure
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($featureName)) {
                return $provider->get($featureName);
            }
        }

        return fn() => false;
    }

    public function getNames(): array
    {
        return array_unique(array_merge(...array_map(fn (ProviderInterface $provider): string => $provider->getNames(), iterator_to_array($this->providers))));
    }
}
