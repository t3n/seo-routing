<?php

declare(strict_types=1);

namespace t3n\SEO\Routing\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\UriFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use t3n\SEO\Routing\RoutingMiddleware;

class RoutingMiddlewareTest extends UnitTestCase
{
    /**
     * @return mixed[]
     */
    public function invalidUrisWithConfig(): array
    {
        // invalidUrl, validUrl, trailingSlash, toLowerCase
        return [
            ['http://dev.local/invalid', 'http://dev.local/invalid/', true, false],
            ['http://dev.local/invalId', 'http://dev.local/invalid/', true, true],
            ['http://dev.local/invalId/', 'http://dev.local/invalid/', false, true]
        ];
    }

    /**
     * @test
     */
    public function uriWithSlashGetsNotModifiedForTrailingSlash(): void
    {
        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $uri = $uriFactory->createUri('http://dev.local/testpath/');
        $newUri = $routingMiddleware->handleTrailingSlash($uri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function uriWithOutSlashGetsModifiedForTrailingSlash(): void
    {
        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $uri = $uriFactory->createUri('http://dev.local/testpath');
        $newUri = $routingMiddleware->handleTrailingSlash($uri);

        $this->assertStringEndsWith('/', (string) $newUri);
    }

    /**
     * @test
     */
    public function uriWithLoweredPathGetsNotModified(): void
    {

        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $uri = $uriFactory->createUri('http://dev.local/testpath/');
        $newUri = $routingMiddleware->handleToLowerCase($uri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function uriWithCamelCasePathGetsModifiedToLowereCase(): void
    {
        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $camelCasePath = '/testPath/';
        $uri = $uriFactory->createUri('http://dev.local' . $camelCasePath);
        $newUri = $routingMiddleware->handleToLowerCase($uri);

        $this->assertNotEquals($camelCasePath, $newUri->getPath());
        $this->assertEquals(strtolower($camelCasePath), $newUri->getPath());
    }

    /**
     * @test
     */
    public function uriWithSpecialCharsDoesNotThrowAnException(): void
    {
        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $uri = $uriFactory->createUri('http://dev.local/äß&/');
        $newUri = $routingMiddleware->handleToLowerCase($uri);
        $newUri = $routingMiddleware->handleTrailingSlash($newUri);

        $this->assertEquals(strtolower((string) $uri), $newUri);
    }

    /**
     * @test
     * @dataProvider invalidUrisWithConfig
     */
    public function ifPathHasChangesRedirect(string $invalidUrl, string $validUrl, bool $trailingSlash, bool $toLowerCase): void
    {
        $configuration = [
            'enable' => [
                'trailingSlash' => $trailingSlash,
                'toLowerCase' => $toLowerCase
            ],
        ];

        $request = self::getRequest($invalidUrl);
        $handler = self::getRequestHandlerWithResponse();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();

        $this->inject($routingMiddleware, 'router', $routerMock);
        $this->inject($routingMiddleware, 'configuration', $configuration);
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $response = $routingMiddleware->process($request, $handler);

        $this->assertEquals("301", $response->getStatusCode());
        $this->assertEquals("http://dev.local/invalid/", $response->getHeader('Location')[0]);

    }

    /**
     * @test
     */
    public function ifPathHasNoChangesDoNotRedirect(): void
    {
        $configuration = [
            'enable' => [
                'trailingSlash' => true,
                'toLowerCase' => true
            ],
        ];

        $validPath = 'http://dev.local/validpath/';

        $request = self::getRequest($validPath);
        $handler = self::getRequestHandlerWithResponse();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();

        $this->inject($routingMiddleware, 'router', $routerMock);
        $this->inject($routingMiddleware, 'configuration', $configuration);
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $response = $routingMiddleware->process($request, $handler);

        $this->assertEquals("200", $response->getStatusCode());
    }

    /**
     * @test
     */
    public function blacklistedUrlShouldNotBeRedirectedToTrailingSlash(): void
    {
        $configuration = [
            'enable' => [
                'trailingSlash' => true,
                'toLowerCase' => false
            ],
        ];

        $blacklistConfiguration = ['/neos.*' => true];

        $blacklistedUrl = 'http://dev.local/neos/test';

        $request = self::getRequest($blacklistedUrl);
        $handler = self::getRequestHandlerWithResponse();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();

        $this->inject($routingMiddleware, 'router', $routerMock);
        $this->inject($routingMiddleware, 'configuration', $configuration);
        $this->inject($routingMiddleware, 'blacklist', $blacklistConfiguration);
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $response = $routingMiddleware->process($request, $handler);

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertEquals([], $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function blacklistedUrlShouldNotBeRedirectedToLowerCase(): void
    {
        $configuration = [
            'enable' => [
                'trailingSlash' => false,
                'toLowerCase' => true
            ],
        ];

        $blacklistConfiguration = ['/neos.*' => true];

        $blacklistedUrl = 'http://dev.local/neos/TEST';

        $request = self::getRequest($blacklistedUrl);
        $handler = self::getRequestHandlerWithResponse();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        /** @var UriFactory $uriFactory */
        $uriFactory = $this->getMockBuilder(UriFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routingMiddleware = new RoutingMiddleware();

        $this->inject($routingMiddleware, 'router', $routerMock);
        $this->inject($routingMiddleware, 'configuration', $configuration);
        $this->inject($routingMiddleware, 'blacklist', $blacklistConfiguration);
        $this->inject($routingMiddleware, 'uriFactory', $uriFactory);

        $response = $routingMiddleware->process($request, $handler);

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertEquals([], $response->getHeader('Location'));
    }

    protected static function getRequest(string $requestPath): ServerRequestInterface
    {
        $request = ServerRequest::fromGlobals();

        return $request->withUri(new Uri($requestPath));
    }

    protected static function getRequestHandlerWithResponse(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }
}
