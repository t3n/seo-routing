<?php

declare(strict_types=1);

namespace t3n\SEO\Routing\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\UriFactory;
use t3n\SEO\Routing\RoutingComponent;

class RoutingComponentTest extends UnitTestCase
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
        $routingComponent = new RoutingComponent();
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $uri = new Uri('http://dev.local/testpath/');
        $newUri = $routingComponent->handleTrailingSlash($uri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function uriWithOutSlashGetsModifiedForTrailingSlash(): void
    {
        $routingComponent = new RoutingComponent();
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $uri = new Uri('http://dev.local/testpath');
        $newUri = $routingComponent->handleTrailingSlash($uri);

        $this->assertStringEndsWith('/', (string) $newUri);
    }

    /**
     * @test
     */
    public function uriWithLoweredPathGetsNotModified(): void
    {
        $routingComponent = new RoutingComponent();
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $uri = new Uri('http://dev.local/testpath/');
        $newUri = $routingComponent->handleToLowerCase($uri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function uriWithCamelCasePathGetsModifiedToLowereCase(): void
    {
        $routingComponent = new RoutingComponent();
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $camelCasePath = '/testPath/';
        $uri = new Uri('http://dev.local' . $camelCasePath);
        $newUri = $routingComponent->handleToLowerCase($uri);

        $this->assertNotEquals($camelCasePath, $newUri->getPath());
        $this->assertEquals(strtolower($camelCasePath), $newUri->getPath());
    }

    /**
     * @test
     */
    public function uriWithSpecialCharsDoesNotThrowAnException(): void
    {
        $routingComponent = new RoutingComponent();
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $uri = new Uri('http://dev.local/äß&/');
        $newUri = $routingComponent->handleToLowerCase($uri);
        $newUri = $routingComponent->handleTrailingSlash($newUri);

        $this->assertEquals("http://dev.local/%c3%a4%c3_&/", (string) $newUri);
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

        $httpRequest = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($invalidUrl));

        $httpResponse = $this->getMockBuilder(Response::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([200])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$httpRequest, $httpResponse])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $routingComponent->handle($componentContext);

        $this->assertEquals("301", $componentContext->getHttpResponse()->getStatusCode());
        $this->assertEquals("http://dev.local/invalid/", $componentContext->getHttpResponse()->getHeader('Location')[0]);

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

        $httpRequest = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($validPath));

        $httpResponse = $this->getMockBuilder(Response::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([200])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$httpRequest, $httpResponse])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $routingComponent->handle($componentContext);

        $this->assertEquals("200", $componentContext->getHttpResponse()->getStatusCode());
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

        $httpRequest = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($blacklistedUrl));

        $httpResponse = $this->getMockBuilder(Response::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([200])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$httpRequest, $httpResponse])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);
        $this->inject($routingComponent, 'blacklist', $blacklistConfiguration);
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $routingComponent->handle($componentContext);

        $this->assertEquals("200", $componentContext->getHttpResponse()->getStatusCode());
        $this->assertEquals([], $componentContext->getHttpResponse()->getHeader('Location'));
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

        $httpRequest = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->setMethods(['getUri'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($blacklistedUrl));

        $httpResponse = $this->getMockBuilder(Response::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([200])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([$httpRequest, $httpResponse])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);
        $this->inject($routingComponent, 'blacklist', $blacklistConfiguration);
        $this->inject($routingComponent, 'uriFactory', new UriFactory());

        $routingComponent->handle($componentContext);

        $this->assertEquals("200", $componentContext->getHttpResponse()->getStatusCode());
        $this->assertEquals([], $componentContext->getHttpResponse()->getHeader('Location'));
    }
}
