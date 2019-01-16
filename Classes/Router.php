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

use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Psr\Http\Message\UriInterface;

class Router extends \Neos\Flow\Mvc\Routing\Router
{
    use BlacklistTrait;

    /**
     * Resolves the current uri and ensures that a trailing slash is present
     */
    public function resolve(ResolveContext $resolveContext): UriInterface
    {
        /** @var Uri $uri */
        $uri = parent::resolve($resolveContext);

        if ($this->matchesBlacklist($uri) === false && isset(pathinfo($uri)['extension']) === false) {
            // $uri needs to be reparsed, because the path often contains the query
            $parsedUri = new Uri((string) $uri);
            $parsedUri->setPath(rtrim($parsedUri->getPath(), '/') . '/');
            return $parsedUri;
        }

        return $uri;
    }
}
