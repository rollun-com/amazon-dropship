<?php

namespace rollun\application\App\Amazon\Client\Factory;

use AmazonOrderList;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use rollun\application\App\Amazon\Client\OrderClient;

/**
 * Class OrderClientFactory
 *
 * 'amazonOrderClient' => [
 *     'class' => OrderClient::class,    // for this factory it's predefined name
 *     'config_section' => "real_name_of_the_config_section_with_amazon_credentials",
 *     'path_to_config' => "/real/path/to_the/amazon-config.php",
 * ]
 *
 * @package rollun\application\App\Amazon\Client\Factory
 */
class OrderClientFactory implements FactoryInterface
{
    const ORDER_CLIENT_KEY = 'amazonOrderClient';
    const ORDER_CLIENT_CLASS_KEY = 'class';
    const ORDER_CLIENT_CONFIG_SECTION_KEY = 'config_section';
    const ORDER_CLIENT_PATH_TO_CONFIG_KEY = 'path_to_config';

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $this->checkConfig($container);

        $amazonOrderList = new AmazonOrderList(
            $serviceConfig[static::ORDER_CLIENT_CONFIG_SECTION_KEY],
            null,
            null,
            $serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY]
        );

        $instance = new $serviceConfig[static::ORDER_CLIENT_CLASS_KEY]($amazonOrderList);
        return $instance;
    }

    /**
     * Checks config keys and file existing and returns serviceConfig
     *
     * @param ContainerInterface $container
     * @return mixed
     */
    protected function checkConfig(ContainerInterface $container)
    {
        $config = $container->get('config');
        if (!isset($config[static::ORDER_CLIENT_KEY])) {
            throw new ServiceNotFoundException("Can't create service because its config is not found.");
        }
        $serviceConfig = $config[static::ORDER_CLIENT_KEY][static::ORDER_CLIENT_KEY];
        if (
            !isset($serviceConfig[static::ORDER_CLIENT_CLASS_KEY]) ||
            !isset($serviceConfig[static::ORDER_CLIENT_CONFIG_SECTION_KEY]) ||
            !isset($serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY])
        ) {
            throw new ServiceNotCreatedException("Can't create service because its config has wrong format.");
        }
        $path = $serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY];
        if (!file_exists($path) || !is_readable($path)) {
            throw new ServiceNotCreatedException("Amazon config file with specified path doen't exist");
        }

        return $serviceConfig;
    }
}