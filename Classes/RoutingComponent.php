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
        $isEnabled = $this->configuration['enable'] === true;

        $uri = $componentContext->getHttpRequest()->getUri();
        $path = $uri->getPath();

        if ($isEnabled === true && $path[-1] !== '/') {
            if ($this->matchesBlacklist($uri) === false && isset(pathinfo($uri)['extension']) === false) {
                $uri->setPath($path . '/');
                $this->redirectToUri($componentContext, $uri);
                return;
            }
        }

        $loweredPath = strtolower($path);

        if ($isEnabled && $this->configuration['toLowerCase'] === true && $path !== $loweredPath) {
            $uri->setPath($loweredPath);
            $this->redirectToUri($componentContext, $uri);
            return;
        }

        parent::handle($componentContext);
    }

    protected function redirectToUri(ComponentContext $componentContext, string $uri): void
    {
        $response = $componentContext->getHttpResponse();
        $response->setStatus($this->configuration['statusCode']);
        $response->setHeader('Location', (string) $uri);

        $componentContext->setParameter(ComponentChain::class, 'cancel', true);
    }
}
