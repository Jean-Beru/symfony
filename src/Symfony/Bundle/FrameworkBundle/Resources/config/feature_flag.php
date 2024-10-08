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

use Symfony\Component\FeatureFlag\ArgumentResolver\ArgumentResolver;
use Symfony\Component\FeatureFlag\FeatureChecker;
use Symfony\Component\FeatureFlag\FeatureCheckerInterface;
use Symfony\Component\FeatureFlag\Provider\ChainProvider;
use Symfony\Component\FeatureFlag\Provider\InMemoryProvider;
use Symfony\Component\FeatureFlag\Provider\ProviderInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

return static function (ContainerConfigurator $container) {
    $container->services()

        ->set('feature_flag.argument_resolver', ArgumentResolver::class)
            ->args([
                '$resolvers' => tagged_iterator('feature_flag.argument_value_resolver'),
            ])
            ->alias(ArgumentResolverInterface::class, 'feature_flag.argument_resolver')

        ->set('feature_flag.provider.in_memory', InMemoryProvider::class)
            ->args([
                '$argumentResolver' => service('feature_flag.argument_resolver'),
                '$features' => abstract_arg('Defined in FeatureFlagPass.'),
            ])
            ->tag('feature_flag.provider')

        ->set('feature_flag.provider', ChainProvider::class)
            ->args([
                '$providers' => tagged_iterator('feature_flag.provider'),
            ])
            ->alias(ProviderInterface::class, 'feature_flag.provider')

        ->set('feature_flag.feature_checker', FeatureChecker::class)
            ->args([
                '$provider' => service('feature_flag.provider'),
            ])
            ->alias(FeatureCheckerInterface::class, 'feature_flag.feature_checker')
    ;
};
