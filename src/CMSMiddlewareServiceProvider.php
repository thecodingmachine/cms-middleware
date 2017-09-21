<?php


namespace TheCodingMachine\CMS\Middleware;


use Interop\Container\ServiceProvider;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use TheCodingMachine\CMS\Block\BlockRendererInterface;
use TheCodingMachine\CMS\Page\PageRegistryInterface;
use TheCodingMachine\CMS\Theme\ThemeFactoryInterface;
use TheCodingMachine\MiddlewareListServiceProvider;
use TheCodingMachine\MiddlewareOrder;

class CMSMiddlewareServiceProvider implements ServiceProviderInterface
{

    public function getFactories()
    {
        return [
            CMSMiddleware::class => [self::class, 'createCmsMiddleware'],
        ];
    }

    public function getExtensions()
    {
        return [
            MiddlewareListServiceProvider::MIDDLEWARES_QUEUE => [self::class,'updatePriorityQueue'],
        ];
    }


    public static function createCmsMiddleware(ContainerInterface $container)
    {
        return new CMSMiddleware($container->get(PageRegistryInterface::class), $container->get(BlockRendererInterface::class));
    }

    public static function updatePriorityQueue(ContainerInterface $container, \SplPriorityQueue $queue = null) : \SplPriorityQueue
    {
        $queue->insert($container->get(CMSMiddleware::class), MiddlewareOrder::ROUTER);
        return $queue;
    }
}
