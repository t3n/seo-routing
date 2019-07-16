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
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Psr\Http\Message\UriInterface;

class RoutingComponent extends \Neos\Flow\Mvc\Routing\RoutingComponent
{
    use BlacklistTrait;

    /**
     * The original routing component uses the concrete router, not the interface
     * so it has to be overwritten here
     *
     * @Flow\Inject
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * @Flow\InjectConfiguration("redirect")
     *
     * @var mixed[]
     */
    protected $configuration;

    /**
     * Redirect automatically to the trailing slash url or lowered url if activated
     */
    public function handle(ComponentContext $componentContext): void
    {
        $trailingSlashIsEnabled = $this->configuration['enable']['trailingSlash'] === true;
        $toLowerCaseIsEnabled = $this->configuration['enable']['toLowerCase'] === true;

        $uri = $componentContext->getHttpRequest()->getUri();
        $path = $uri->getPath();

        if ($trailingSlashIsEnabled) {
            $this->handleTrailingSlash($componentContext, $uri, $path);
        }

        if ($toLowerCaseIsEnabled) {
            $this->handleToLowerCase($componentContext, $uri, $path);
        }

        parent::handle($componentContext);
    }

    protected function handleTrailingSlash(ComponentContext $componentContext, UriInterface $uri, string $path): void
    {
        if ($path[-1] === '/') {
            return;
        }

        if ($this->matchesBlacklist($uri) === false) {
            $uri->setPath($path . '/');
            $this->redirectToUri($componentContext, $uri);
        }
    }

    protected function handleToLowerCase(ComponentContext $componentContext, UriInterface $uri, string $path): void
    {
        $loweredPath = strtolower($path);

        if ($path !== $loweredPath) {
            $uri->setPath($loweredPath);
            $this->redirectToUri($componentContext, $uri);
        }
    }

    protected function redirectToUri(ComponentContext $componentContext, UriInterface $uri): void
    {
        $response = $componentContext->getHttpResponse();
        $response->setStatus($this->configuration['statusCode']);
        $response->setHeader('Location', (string) $uri);

        $componentContext->setParameter(ComponentChain::class, 'cancel', true);
    }
}
