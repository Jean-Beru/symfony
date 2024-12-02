<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\FeatureFlag\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\FeatureFlag\Attribute\Feature;
use Symfony\Component\FeatureFlag\FeatureCheckerInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeatureAttributeListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly FeatureCheckerInterface $featureChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerArgumentsEvent::class => ['onControllerArguments', 15],
        ];
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        /** @var Feature[] $attributes */
        if ([] === ($attributes = $event->getAttributes(Feature::class))) {
            return;
        }

        foreach ($attributes as $attribute) {
            if (!$this->featureChecker->isEnabled($attribute->name, $attribute->expected)) {
                if (null !== $attribute->statusCode) {
                    throw new HttpException($attribute->statusCode, $attribute->message ?: '', code: $attribute->exceptionCode ?? 0);
                }

                throw new NotFoundHttpException();
            }
        }
    }
}
