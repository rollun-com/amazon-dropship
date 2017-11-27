<?php

namespace rollun\amazonItemSearch\Callback\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use rollun\amazonItemSearch\Callback\AmazonItemSearchTaskCallback;

/**
 * Class AmazonOrderTaskCallbackFactory
 *
 * <code>
 * AmazonItemSearchTaskCallback::class => [
 *     'callback' => 'taskAmazonItemSearch',
 *     'schedule' => [
 *         'hours' => '*',
 *         'minutes' => 15,
 *     ],
 * ],
 * </code>
 *
 * @package rollun\amazonDropship\Callback\Factory
 */
class AmazonItemSearchTaskCallbackFactory implements FactoryInterface
{
    const CALLBACK_KEY = 'callback';
    const SCHEDULE_KEY = 'schedule';

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config[$requestedName])) {
            throw new ServiceNotFoundException("The config for specified service is not found");
        }
        $serviceConfig = $config[$requestedName];
        if (!isset($serviceConfig[static::CALLBACK_KEY])) {
            throw new ServiceNotCreatedException("The required parameter \"" . static::CALLBACK_KEY
                . "\" is not found in the service config");
        }
        if (!$container->has($serviceConfig[static::CALLBACK_KEY])) {
            throw new ServiceNotCreatedException("Can't create the service \"{$requestedName}\" because "
                . "callback with name \"{$serviceConfig[static::CALLBACK_KEY]}\" doesn't exist");
        }
        $callback = $container->get($serviceConfig[static::CALLBACK_KEY]);
        unset($serviceConfig[static::CALLBACK_KEY]);
        if (!isset($serviceConfig[static::SCHEDULE_KEY])) {
            $serviceConfig[static::SCHEDULE_KEY] = [
                'hours' => ['*'],
                'minutes' => ['*'],
            ];
        }
        $instance = new AmazonItemSearchTaskCallback($callback, $serviceConfig);
        return $instance;
    }
}