<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\FeatureFlag\EventListener\FeatureAttributeListener;
use Symfony\Component\FeatureFlag\FeatureChecker;
use Symfony\Component\FeatureFlag\FeatureCheckerInterface;
use Symfony\Component\FeatureFlag\Provider\InMemoryProvider;
use Symfony\Component\FeatureFlag\Provider\ProviderInterface;

return static function (ContainerConfigurator $container) {
    $container->services()

        ->set('feature_flag.provider.in_memory', InMemoryProvider::class)
            ->args([
                '$features' => abstract_arg('Defined in FeatureFlagPass.'),
            ])
            ->alias(ProviderInterface::class, 'feature_flag.provider.in_memory')

        ->set('feature_flag.feature_checker', FeatureChecker::class)
            ->args([
                '$provider' => service('feature_flag.provider.in_memory'),
            ])
            ->alias(FeatureCheckerInterface::class, 'feature_flag.feature_checker')


        ->set('feature_flag.feature_attribute_listener', FeatureAttributeListener::class)
            ->args([
                service('feature_flag.feature_checker'),
            ])
            ->tag('kernel.event_subscriber')
    ;
};
