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

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingMiddleware extends \Neos\Flow\Mvc\Routing\RoutingMiddleware
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
     * @Flow\Inject
     *
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * @Flow\InjectConfiguration("redirect")
     *
     * @var mixed[]
     */
    protected $configuration = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $trailingSlashIsEnabled = isset($this->configuration['enable']['trailingSlash']) ? $this->configuration['enable']['trailingSlash'] === true : false;
        $toLowerCaseIsEnabled = isset($this->configuration['enable']['toLowerCase']) ? $this->configuration['enable']['toLowerCase'] === true : false;

        $uri = $request->getUri();

        $oldPath = $uri->getPath();

        if ($trailingSlashIsEnabled) {
            $uri = $this->handleTrailingSlash($uri);
        }

        if ($toLowerCaseIsEnabled) {
            $uri = $this->handleToLowerCase($uri);
        }

        $response = $this->redirectIfNecessary($uri, $oldPath);
        if ($response !== null) {
            return $response;
        }

        return parent::process($request->withUri($uri), $handler);
    }

    public function handleTrailingSlash(UriInterface $uri): UriInterface
    {
        if (strlen($uri->getPath()) === 0) {
            return $uri;
        }

        if ($this->matchesBlacklist($uri) === false && ! array_key_exists('extension', pathinfo($uri->getPath()))) {
            $parsedUri = $this->uriFactory->createUri((string) $uri . '/');
            return $parsedUri->withPath(rtrim($parsedUri->getPath(), '/') . '/');
        }

        return $uri;
    }

    public function handleToLowerCase(UriInterface $uri): UriInterface
    {
        $loweredPath = strtolower($uri->getPath());

        if ($this->matchesBlacklist($uri) === false && $uri->getPath() !== $loweredPath) {
            $newUri = str_replace($uri->getPath(), $loweredPath, (string) $uri);
            $uri = $this->uriFactory->createUri($newUri);
        }

        return $uri;
    }

    protected function redirectIfNecessary(UriInterface $uri, string $oldPath): ?ResponseInterface
    {
        if ($uri->getPath() === $oldPath) {
            return null;
        }

        //set default redirect statusCode if configuration is not set
        $statusCode = array_key_exists('statusCode', $this->configuration) ? $this->configuration['statusCode'] : 301;
        $response = new Response($statusCode);

        return $response->withAddedHeader('Location', (string) $uri);
    }
}
