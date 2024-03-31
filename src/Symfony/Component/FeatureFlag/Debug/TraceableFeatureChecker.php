<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\Debug;

use Symfony\Component\FeatureFlag\FeatureCheckerInterface;

final class TraceableFeatureChecker implements FeatureCheckerInterface
{
    /** @var array<string, list<array{mixed, bool}>> */
    private array $checks = [];
    /** @var array<string, mixed> */
    private array $values = [];

    public function __construct(
        private readonly FeatureCheckerInterface $decorated,
    ) {
    }

    public function isEnabled(string $featureName, mixed $expectedValue = true): bool
    {
        $this->checks[$featureName] ??= [];
        $isEnabled = $this->decorated->isEnabled($featureName, $expectedValue);
        $this->checks[$featureName][] = [$expectedValue, $isEnabled];

        // Force logging value. It has no cost since value is cached by decorated FeatureChecker.
        $this->getValue($featureName);

        return $isEnabled;
    }

    public function isDisabled(string $featureName, mixed $expectedValue = true): bool
    {
        return !$this->isEnabled($featureName, $expectedValue);
    }

    public function getValue(string $featureName): mixed
    {
        return $this->values[$featureName] ??= $this->decorated->getValue($featureName);
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
