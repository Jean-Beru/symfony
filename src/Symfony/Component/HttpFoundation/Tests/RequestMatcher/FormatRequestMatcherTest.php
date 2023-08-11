<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Tests\RequestMatcher;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\FormatRequestMatcher;

class FormatRequestMatcherTest extends TestCase
{
    /**
     * @dataProvider getData
     */
    public function test(?string $requestFormat, array|string $matcherFormat, bool $isMatch)
    {
        $matcher = new FormatRequestMatcher($matcherFormat);

        $request = Request::create('/');
        $request->attributes->set('_format', $requestFormat);
        $this->assertSame($isMatch, $matcher->matches($request));

        $request = Request::create('/');
        $request->setRequestFormat($requestFormat);
        $this->assertSame($isMatch, $matcher->matches($request));
    }

    public static function getData()
    {
        yield 'With a request without format' => [null, 'json', false];
        yield 'With an empty array' => ['json', [], true];
        yield 'With the exact value' => ['json', 'json', true];
        yield 'With comma-separated formats' => ['json', 'json,xml', true];
        yield 'With an array of formats' => ['json', ['json', 'xml'], true];
        yield 'With comma-separated capitalized formats' => ['json', 'Json,XmL', true];
        yield 'With an array of capitalized formats' => ['json', ['Json', 'XmL'], true];
    }
}
