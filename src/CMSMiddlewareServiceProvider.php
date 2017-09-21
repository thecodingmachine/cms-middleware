<?php


namespace TheCodingMachine\CMS\Middleware;


use Interop\Container\ServiceProvider;
use Psr\Container\ContainerInterface;
use TheCodingMachine\CMS\Block\BlockRendererInterface;
use TheCodingMachine\CMS\Page\PageRegistryInterface;
use TheCodingMachine\CMS\Theme\ThemeFactoryInterface;
use TheCodingMachine\MiddlewareListServiceProvider;
use TheCodingMachine\MiddlewareOrder;

class CMSMiddlewareServiceProvider implements ServiceProvider
{

    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the entry, aka the **factory**
     *
     * Factories have the following signature:
     *        function(ContainerInterface $container, callable $getPrevious = null)
     *
     * About factories parameters:
     *
     * - the container (instance of `Interop\Container\ContainerInterface`)
     * - a callable that returns the previous entry if overriding a previous entry, or `null` if not
     *
     * @return callable[]
     */
    public function getServices()
    {
        return [
            CMSMiddleware::class => [self::class, 'createCmsMiddleware'],
            MiddlewareListServiceProvider::MIDDLEWARES_QUEUE => [self::class,'updatePriorityQueue'],
        ];
    }

    public static function createCmsMiddleware(ContainerInterface $container)
    {
        return new CMSMiddleware($container->get(PageRegistryInterface::class), $container->get(BlockRendererInterface::class));
    }

    public static function updatePriorityQueue(ContainerInterface $container, callable $previous = null) : \SplPriorityQueue
    {
        if ($previous) {
            $priorityQueue = $previous();
            $priorityQueue->insert($container->get(CMSMiddleware::class), MiddlewareOrder::ROUTER);
            return $priorityQueue;
        } else {
            throw new InvalidArgumentException("Could not find declaration for service '".MiddlewareListServiceProvider::MIDDLEWARES_QUEUE."'.");
        }
    }
}
