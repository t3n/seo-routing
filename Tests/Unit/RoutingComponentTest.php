<?php

declare(strict_types=1);

namespace t3n\SEO\Routing\Tests\Unit;

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Tests\UnitTestCase;
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

        $uri = new Uri('http://dev.local/äß&/');
        $newUri = $routingComponent->handleToLowerCase($uri);
        $newUri = $routingComponent->handleTrailingSlash($newUri);

        $this->assertEquals($uri, $newUri);
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

        $httpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->setMethods(['getUri', 'setStatus'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($invalidUrl));

        $httpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['setStatus', 'setHeader'])->getMock();
        $httpResponse->expects($this->once())->method('setStatus')->with(301);
        $httpResponse->expects($this->once())->method('setHeader')->with('Location', $validUrl);

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->setMethods(['getHttpRequest', 'getHttpResponse'])->getMock();
        $componentContext->method('getHttpRequest')->willReturn($httpRequest);
        $componentContext->method('getHttpResponse')->willReturn($httpResponse);

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);

        $routingComponent->handle($componentContext);
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

        $httpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->setMethods(['getUri', 'setStatus'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($validPath));

        $httpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['setStatus', 'setHeader'])->getMock();
        $httpResponse->expects($this->never())->method('setStatus');
        $httpResponse->expects($this->never())->method('setHeader');

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->setMethods(['getHttpRequest', 'getHttpResponse'])->getMock();
        $componentContext->method('getHttpRequest')->willReturn($httpRequest);
        $componentContext->method('getHttpResponse')->willReturn($httpResponse);

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);

        $routingComponent->handle($componentContext);
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

        $httpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->setMethods(['getUri', 'setStatus'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($blacklistedUrl));

        $httpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['setStatus', 'setHeader'])->getMock();
        $httpResponse->expects($this->never())->method('setStatus');
        $httpResponse->expects($this->never())->method('setHeader');

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->setMethods(['getHttpRequest', 'getHttpResponse'])->getMock();
        $componentContext->method('getHttpRequest')->willReturn($httpRequest);
        $componentContext->method('getHttpResponse')->willReturn($httpResponse);

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);
        $this->inject($routingComponent, 'blacklist', $blacklistConfiguration);

        $routingComponent->handle($componentContext);
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

        $httpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->setMethods(['getUri', 'setStatus'])->getMock();
        $httpRequest->method('getUri')->willReturn(new Uri($blacklistedUrl));

        $httpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->setMethods(['setStatus', 'setHeader'])->getMock();
        $httpResponse->expects($this->never())->method('setStatus');
        $httpResponse->expects($this->never())->method('setHeader');

        /** @var ComponentContext $componentContext */
        $componentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->setMethods(['getHttpRequest', 'getHttpResponse'])->getMock();
        $componentContext->method('getHttpRequest')->willReturn($httpRequest);
        $componentContext->method('getHttpResponse')->willReturn($httpResponse);

        $routerMock = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['route'])->getMock();
        $routerMock->method('route')->willReturn([]);

        $routingComponent = new RoutingComponent();

        $this->inject($routingComponent, 'router', $routerMock);
        $this->inject($routingComponent, 'configuration', $configuration);
        $this->inject($routingComponent, 'blacklist', $blacklistConfiguration);

        $routingComponent->handle($componentContext);
    }
}
