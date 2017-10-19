<?php

namespace rollun\amazonDropship\Amazon\Client\Factory;

use AmazonOrderList;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class AmazonOrderListFactory implements  FactoryInterface
{
    const AMAZON_ORDER_LIST_KEY = 'amazon_order_list';
    const ORDER_CLIENT_CONFIG_SECTION_KEY = 'config_section';
    const ORDER_CLIENT_MOCK_MODE_KEY = 'mock_mode';
    const ORDER_CLIENT_MOCK_FILES_KEY = 'mock_files';
    const ORDER_CLIENT_PATH_TO_CONFIG_KEY = 'path_to_config';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config[static::AMAZON_ORDER_LIST_KEY])) {
            throw new ServiceNotFoundException("Can't create service \"{$requestedName}\" because its config is not found.");
        }
        $serviceConfig = $config[static::AMAZON_ORDER_LIST_KEY];
        if (
            !isset($serviceConfig[static::ORDER_CLIENT_CONFIG_SECTION_KEY]) ||
            !isset($serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY])
        ) {
            throw new ServiceNotCreatedException("Can't create service because its config has wrong format.");
        }
        $path = $serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY];
        if (!file_exists($path) || !is_readable($path)) {
            throw new ServiceNotCreatedException("Amazon config file with specified path doesn't exist");
        }
        $configSection = $serviceConfig[static::ORDER_CLIENT_CONFIG_SECTION_KEY];
        $pathToConfig = $serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY];
        $mockMode = (isset($serviceConfig[static::ORDER_CLIENT_MOCK_MODE_KEY])) ? $serviceConfig[static::ORDER_CLIENT_MOCK_MODE_KEY] : null;
        $mockFiles = (isset($serviceConfig[static::ORDER_CLIENT_MOCK_FILES_KEY])) ? $serviceConfig[static::ORDER_CLIENT_MOCK_FILES_KEY] : null;
        $instance = new AmazonOrderList($configSection, $mockMode, $mockFiles, $pathToConfig);
        return $instance;
    }

}