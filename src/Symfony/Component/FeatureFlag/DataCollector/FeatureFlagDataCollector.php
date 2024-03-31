<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\DataCollector;

use Symfony\Component\FeatureFlag\Debug\TraceableFeatureChecker;
use Symfony\Component\FeatureFlag\FeatureRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

final class FeatureFlagDataCollector extends DataCollector implements LateDataCollectorInterface
{
    public function __construct(
        private readonly FeatureRegistryInterface $featureRegistry,
        private readonly TraceableFeatureChecker $featureChecker,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
    }

    public function lateCollect(): void
    {
        $checks = $this->featureChecker->getChecks();
        $values = $this->featureChecker->getValues();

        $this->data['features'] = [];
        $this->data['ratios'] = [];
        foreach ($this->featureRegistry->getNames() as $featureName) {
            $this->data['features'][$featureName] ??= [];
            $this->data['ratios'][$featureName] ??= [0, 0];
            $this->data['ratios'][$featureName][1]++;

            foreach ($checks[$featureName] ?? [] as [$expectedValue, $isEnabled]) {
                if ($isEnabled) {
                    $this->data['ratios'][$featureName][0]++;
                }

                $this->data['features'][$featureName][] = [
                    'expected_value' => $this->cloneVar($expectedValue ?? null),
                    'is_enabled' => $isEnabled,
                    'value' => $this->cloneVar($values[$featureName] ?? null),
                ];
            }
        }
    }

    public function getFeatures(): array
    {
        return $this->data['features'] ?? [];
    }

    public function getRatios(): array
    {
        return $this->data['ratios'] ?? [];
    }

    public function getName(): string
    {
        return 'feature_flag';
    }
}
