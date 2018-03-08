<?php
namespace Yeebase\SEO\Routing;

use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Psr\Http\Message\UriInterface;

class Router extends \Neos\Flow\Mvc\Routing\Router
{
    /**
     * Resolves the current uri and ensures that a trailing slash is present
     *
     * @param ResolveContext $resolveContext
     * @return UriInterface
     */
    public function resolve(ResolveContext $resolveContext): UriInterface
    {
        /** @var Uri $uri */
        $uri = parent::resolve($resolveContext);

        $info = pathinfo($uri);
        if (!isset($info['extension'])) {
            // $uri needs to be reparsed, bc the path often often contains the query
            $parsedUri = new Uri((string)$uri);
            $parsedUri->setPath(rtrim($parsedUri->getPath(), '/') . '/');
            return $parsedUri;
        }

        return $uri;
    }
}
