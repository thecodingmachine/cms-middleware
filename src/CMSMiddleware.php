<?php


namespace TheCodingMachine\CMS\Middleware;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheCodingMachine\CMS\Block\BlockRenderer;
use TheCodingMachine\CMS\Page\PageRegistryInterface;
use TheCodingMachine\CMS\Theme\ThemeFactoryInterface;
use Zend\Diactoros\Response;

class CMSMiddleware implements MiddlewareInterface
{
    /**
     * @var PageRegistryInterface
     */
    private $pageRegistry;
    /**
     * @var BlockRenderer
     */
    private $blockRenderer;

    public function __construct(PageRegistryInterface $pageRegistry, BlockRenderer $blockRenderer)
    {
        $this->pageRegistry = $pageRegistry;
        $this->blockRenderer = $blockRenderer;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // Let's only deal with GET requests.
        if ($request->getMethod() !== 'GET') {
            return $delegate->process($request);
        }

        $page = $this->pageRegistry->getPage($request);

        if ($page === null) {
            return $delegate->process($request);
        }

        $stream = $this->blockRenderer->renderBlock($page);

        return new Response($stream);
    }
}
