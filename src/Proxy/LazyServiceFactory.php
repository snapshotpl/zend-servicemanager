<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Proxy;

use Interop\Container\ContainerInterface;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;
use function sprintf;

/**
 * Delegator factory responsible of instantiating lazy loading value holder proxies of
 * given services at runtime
 *
 * @link https://github.com/Ocramius/ProxyManager/blob/master/docs/lazy-loading-value-holder.md
 */
final class LazyServiceFactory implements DelegatorFactoryInterface
{
    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var string[] map of service names to class names
     */
    private $servicesMap;

    /**
     * @param LazyLoadingValueHolderFactory $proxyFactory
     * @param string[]                      $servicesMap  a map of service names to class names of their
     *                                                    respective classes
     */
    public function __construct(LazyLoadingValueHolderFactory $proxyFactory, array $servicesMap)
    {
        $this->proxyFactory = $proxyFactory;
        $this->servicesMap  = $servicesMap;
    }

    /**
     * {@inheritDoc}
     *
     * @return VirtualProxyInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback, array $options = null): VirtualProxyInterface
    {
        $initializer = function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($callback) {
            $proxy->setProxyInitializer(null);
            $wrappedInstance = $callback();

            return true;
        };

        if (isset($this->servicesMap[$name])) {
            return $this->proxyFactory->createProxy($this->servicesMap[$name], $initializer);
        }

        throw new Exception\ServiceNotFoundException(
            sprintf('The requested service "%s" was not found in the provided services map', $name)
        );
    }
}
