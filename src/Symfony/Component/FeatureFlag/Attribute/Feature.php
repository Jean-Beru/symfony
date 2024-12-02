<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\Attribute;

/**
 * Checks if a feature flag is enabled to access to some resource.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class Feature
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $expected = true,
        public readonly ?string $message = null,
        public readonly ?int $statusCode = null,
        public readonly ?int $exceptionCode = null,
    ) {
    }
}
