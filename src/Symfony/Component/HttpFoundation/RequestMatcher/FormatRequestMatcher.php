<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\RequestMatcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Checks the HTTP method of a Request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FormatRequestMatcher implements RequestMatcherInterface
{
    /**
     * @var string[]
     */
    private array $formats = [];

    /**
     * @param string[]|string $formats An HTTP method or an array of HTTP formats
     *                                 Strings can contain a comma-delimited list of formats
     */
    public function __construct(array|string $formats)
    {
        $this->formats = array_reduce(array_map(strtolower(...), (array) $formats), static fn (array $formats, string $format) => array_merge($formats, preg_split('/\s*,\s*/', $format)), []);
    }

    public function matches(Request $request): bool
    {
        if (!$this->formats) {
            return true;
        }

        return \in_array($request->getRequestFormat(), $this->formats, true);
    }
}
