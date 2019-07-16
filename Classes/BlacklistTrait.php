<?php

declare(strict_types=1);

namespace t3n\SEO\Routing;

/**
 * This file is part of the t3n.SEO.Routing package.
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
     * @Flow\InjectConfiguration(package="t3n.SEO.Routing", path="blacklist")
     *
     * @var mixed[]
     */
    protected $blacklist;

    protected function matchesBlacklist(UriInterface $uri): bool
    {
        if (!is_array($this->blacklist)) {
            return false;
        }

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
