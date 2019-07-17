<?php

declare(strict_types=1);

namespace t3n\SEO\Routing\Tests\Unit;

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Tests\UnitTestCase;
use t3n\SEO\Routing\RoutingComponent;

class RoutingComponentTest extends UnitTestCase
{

    /**
     * @test
     */
    public function uriWithSlashGetsNotModifiedForTrailingSlash()
    {
        $routingComponent = new RoutingComponent();

        $uri    = new Uri('http://dev.local/testpath/');
        $newUri = $routingComponent->handleTrailingSlash($uri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function uriWithOutSlashGetsModifiedForTrailingSlash()
    {
        $routingComponent = new RoutingComponent();

        $uri    = new Uri('http://dev.local/testpath');
        $newUri = $routingComponent->handleTrailingSlash($uri);

        $this->assertStringEndsWith('/', (string)$newUri);
    }

    /**
     * @test
     */
    public function uriWithLoweredPathGetsNotModified()
    {
        $routingComponent = new RoutingComponent();

        $uri    = new Uri('http://dev.local/testpath/');
        $newUri = $routingComponent->handleToLowerCase($uri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function uriWithCamelCasePathGetsModifiedToLowereCase()
    {
        $routingComponent = new RoutingComponent();

        $camelCasePath = '/testPath/';
        $uri           = new Uri('http://dev.local' . $camelCasePath);
        $newUri        = $routingComponent->handleToLowerCase($uri);

        $this->assertNotEquals($camelCasePath, $newUri->getPath());
        $this->assertEquals(strtolower($camelCasePath), $newUri->getPath());
    }

    /**
     * @test
     */
    public function uriWithSpecialCharsDoesNotThrowAnException()
    {
        $routingComponent = new RoutingComponent();

        $uri    = new Uri('http://dev.local/äß&/');
        $newUri = $routingComponent->handleToLowerCase($uri);
        $newUri = $routingComponent->handleTrailingSlash($newUri);

        $this->assertEquals($uri, $newUri);
    }

    /**
     * @test
     */
    public function ifPathHasNoChangesDoNotRedirect()
    {
        $httpRequest = new Request([], [], [], []);
        $httpResponse = new Response();
        $componentContext = new ComponentContext($httpRequest, $httpResponse);

        $oldUri = new Uri('http://dev.local/äß&/');
        $newUri = new Uri('http://dev.local/äß&/');

        $routingComponent = new RoutingComponent();
        $redirect = $routingComponent->redirectIfNecessary($componentContext, $newUri, $oldUri->getPath());

        $this->assertEquals(false, $redirect);
    }

    /**
     * @test
     */
    public function ifPathHasChangesRedirect()
    {
        $httpRequest = new Request([], [], [], []);
        $httpResponse = new Response();
        $componentContext = new ComponentContext($httpRequest, $httpResponse);

        $oldUri = new Uri('http://dev.local/teST');
        $newUri = new Uri('http://dev.local/test/');

        $routingComponent = new RoutingComponent();
        $redirect = $routingComponent->redirectIfNecessary($componentContext, $newUri, $oldUri->getPath());

        $this->assertEquals(true, $redirect);
    }
}
