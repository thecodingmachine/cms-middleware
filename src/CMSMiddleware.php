<?php


namespace TheCodingMachine\CMS\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\CMS\Block\BlockRenderer;
use TheCodingMachine\CMS\Block\CacheableBlock;
use TheCodingMachine\CMS\Page\PageRegistryInterface;
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Let's only deal with GET requests.
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }

        $page = $this->pageRegistry->getPage($request);

        if ($page === null) {
            return $handler->handle($request);
        }

        $stream = $this->blockRenderer->renderBlock($page);

        $response = new Response($stream);

        if ($page instanceof CacheableBlock) {
            $response = $response->withHeader('Expires', gmdate('D, d M Y H:i:s T', time() + $page->getTtl()));
        }

        return $response;
    }
}
