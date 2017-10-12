<?php

namespace rollun\amazonDropship\Amazon\Callback\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use rollun\amazonDropship\Amazon\Callback\AmazonOrderTaskCallback;

class AmazonOrderTaskCallbackFactory implements FactoryInterface
{
    const MODE_KEY = 'mode';

    const CALLBACK_KEY = 'callback';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config[$requestedName])) {
            throw new ServiceNotFoundException("The config for specified service is not found");
        }
        $serviceConfig = $config[$requestedName];
        if (!isset($serviceConfig[static::MODE_KEY])) {
            throw new ServiceNotCreatedException("The required parameter " . static::MODE_KEY
                . " is not found in the service config");
        }
        if (!isset($serviceConfig[static::CALLBACK_KEY])) {
            throw new ServiceNotCreatedException("The required parameter " . static::CALLBACK_KEY
                . " is not found in the service config");
        }
        if (!$container->has($serviceConfig[static::CALLBACK_KEY])) {
            throw new ServiceNotCreatedException("Can't create the service \"{$requestedName}\" because "
                . "callback with name \"{$serviceConfig[static::CALLBACK_KEY]}\" doesn't exist");
        }
        $callback = $container->get($serviceConfig[static::CALLBACK_KEY]);
        $instance = new AmazonOrderTaskCallback($callback, $serviceConfig);
        return $instance;
    }
}