<?php

namespace TheCodingMachine\CMS\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheCodingMachine\CMS\Block\Block;
use TheCodingMachine\CMS\Block\BlockRenderer;
use TheCodingMachine\CMS\Page\StaticPageRegistry;
use TheCodingMachine\CMS\Theme\AggregateThemeFactory;
use TheCodingMachine\CMS\Theme\SubThemeDescriptor;
use TheCodingMachine\CMS\Theme\SubThemeFactory;
use TheCodingMachine\CMS\Theme\TwigThemeDescriptor;
use TheCodingMachine\CMS\Theme\TwigThemeFactory;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class CMSMiddlewareTest extends TestCase
{
    public function testCMSMiddleware()
    {
        // This is actually an integration test.
        $loader = new \Twig_Loader_Array([
            'index.html' => "Header: {{ header }}\nContent: {{ content }}",
        ]);

        $twig = new \Twig_Environment($loader);


        $pageRegistry = new StaticPageRegistry([
            '/foo' => new Block(
                new SubThemeDescriptor(
                    new TwigThemeDescriptor(
                        'index.html'
                    ),
                    [
                        'header' => 'Some header' // Fixme: how to handle blocks here? (think: menus!) => Maybe a "page without URL object???"
                    ]
                ),
                [
                    'content' => 'Foobar'
                ]
            )
        ]);

        $themeFactory = new AggregateThemeFactory([]);

        $blockRenderer = new BlockRenderer($themeFactory);

        $themeFactory->addThemeFactory(new SubThemeFactory($themeFactory));
        $themeFactory->addThemeFactory(new TwigThemeFactory($twig, $blockRenderer));

        $middleware = new CMSMiddleware($pageRegistry, $blockRenderer);

        $delegate = new class implements DelegateInterface {

            /**
             * Dispatch the next available middleware and return the response.
             *
             * @param ServerRequestInterface $request
             *
             * @return ResponseInterface
             */
            public function process(ServerRequestInterface $request)
            {
                return new TextResponse('Not found', 404);
            }
        };

        $serverRequest = (new ServerRequest())->withUri(new Uri('http://exemple.com/foo'));
        $response =  $middleware->process($serverRequest, $delegate);
        $this->assertSame("Header: Some header\nContent: Foobar", $response->getBody()->getContents());

        $serverRequest = (new ServerRequest())->withUri(new Uri('http://exemple.com/bar'));
        $response =  $middleware->process($serverRequest, $delegate);
        $this->assertSame(404, $response->getStatusCode());
    }
}
