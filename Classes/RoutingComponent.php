<?php
namespace Yeebase\SEO\Routing;

/**
 * This file is part of the Yeebase.Readiness package.
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
    /**
     * The original routing component uses the concret router, not the interface
     * so it has to be overwritten here
     *
     * @Flow\Inject
     * @var RouterInterface
     */
    protected $router;

    /**
     * @Flow\InjectConfiguration("redirect")
     * @var array
     */
    protected $configuration;

    /**
     * Redirect automatically to the trailing slash url
     *
     * @param ComponentContext $componentContext
     */
    public function handle(ComponentContext $componentContext)
    {
        $uri = $componentContext->getHttpRequest()->getUri();

        if ($this->configuration['enable'] === true && $uri->getPath()[-1] !== '/') {
            $info = pathinfo($uri);
            if (!isset($info['extension'])) {
                $uri->setPath($uri->getPath() . '/');
                $response = $componentContext->getHttpResponse();
                $response->setStatus($this->configuration['statusCode']);
                $response->setHeader('Location', (string) $uri);
                $componentContext->setParameter(ComponentChain::class, 'cancel', true);
                return;
            }
        }

        parent::handle($componentContext);
    }
}
