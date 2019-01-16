<?php

declare(strict_types=1);

namespace Yeebase\SEO\Routing;

/**
 * This file is part of the Yeebase.SEO.Routing package.
 *
 * (c) 2018 yeebase media GmbH
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\UriInterface;

trait BlacklistTrait
{
    /**
     * @Flow\InjectConfiguration(package="Yeebase.SEO.Routing", path="blacklist")
     *
     * @var mixed[]
     */
    protected $blacklist;

    protected function matchesBlacklist(UriInterface $uri): bool
    {
        $path = $uri->getPath();
        foreach ($this->blacklist as $rawPattern => $active) {
            $pattern = '/' . str_replace('/', '\/', $rawPattern) . '/';
            if ($active === true && preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
