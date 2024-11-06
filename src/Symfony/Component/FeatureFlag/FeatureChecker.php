<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag;

use Symfony\Component\FeatureFlag\Provider\ProviderInterface;

/**
 * @experimental
 */
final class FeatureChecker implements FeatureCheckerInterface
{
    private array $cache = [];

    public function __construct(
        private readonly ProviderInterface $provider,
    ) {
    }

    public function isEnabled(string $featureName): bool
    {
        return true === $this->getValue($featureName);
    }

    public function getValue(string $featureName): mixed
    {
        if (isset($this->cache[$featureName])) {
            return $this->cache[$featureName];
        }

        $feature = $this->provider->get($featureName) ?? fn () => false;

        return $this->cache[$featureName] = $feature();
    }
}
