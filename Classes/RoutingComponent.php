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
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (! is_array($this->configuration)) {
            $this->configuration = [];
        }
    }

    /**
     * Redirect automatically to the trailing slash url or lowered url if activated
     */
    public function handle(ComponentContext $componentContext): void
    {
        $trailingSlashIsEnabled = isset($this->configuration['enable']['trailingSlash']) ? $this->configuration['enable']['trailingSlash'] === true : false;
        $toLowerCaseIsEnabled = isset($this->configuration['enable']['toLowerCase']) ? $this->configuration['enable']['toLowerCase'] === true : false;

        $uri = $componentContext->getHttpRequest()->getUri();
        $oldPath = $uri->getPath();

        if ($trailingSlashIsEnabled) {
            $uri = $this->handleTrailingSlash($uri);
        }

        if ($toLowerCaseIsEnabled) {
            $uri = $this->handleToLowerCase($uri);
        }

        $this->redirectIfNecessary($componentContext, $uri, $oldPath);

        parent::handle($componentContext);
    }

    public function handleTrailingSlash(UriInterface $uri): UriInterface
    {
        if (strlen($uri->getPath()) === 0 || $uri->getPath()[-1] === '/') {
            return $uri;
        }

        if ($this->matchesBlacklist($uri) === false && ! array_key_exists('extension', pathinfo($uri->getPath()))) {
            $uri->setPath($uri->getPath() . '/');
        }

        return $uri;
    }

    public function handleToLowerCase(UriInterface $uri): UriInterface
    {
        $loweredPath = strtolower($uri->getPath());

        if ($uri->getPath() !== $loweredPath) {
            $uri->setPath($loweredPath);
        }

        return $uri;
    }

    public function redirectIfNecessary(ComponentContext $componentContext, UriInterface $uri, string $oldPath): bool
    {
        if ($uri->getPath() === $oldPath) {
            return false;
        }

        //set default redirect statusCode if configuration is not set
        $statusCode = array_key_exists('statusCode', $this->configuration) ? $this->configuration['statusCode'] : 301;

        $response = $componentContext->getHttpResponse();
        $response->withStatus((int) $statusCode);
        $response->withHeader('Location', (string) $uri);

        $componentContext->setParameter(ComponentChain::class, 'cancel', true);

        return true;
    }
}
