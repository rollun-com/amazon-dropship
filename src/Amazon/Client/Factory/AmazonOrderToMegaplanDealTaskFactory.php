<?php

namespace rollun\amazonDropship\Amazon\Client\Factory;

use AmazonOrderList;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class OrderClientFactory
 *
 * 'taskAmazonOrderToMegaplanDeal' => [
 *     'class' => OrderClient::class,    // for this factory it's predefined name
 *     'config_section' => "real_name_of_the_config_section_with_amazon_credentials",
 *     'path_to_config' => "/real/path/to_the/amazon-config.php",
 *     'megaplan_dataStore' => 'real_name_of_megaplan_dataStore_or_aspect_which_encapsulates_one',
 *     'tracking_number_dataStore' => 'real_name_of_datStore_which_can_return_the_tracking_number',
 *     'logger' => 'real_name_logger_service',  // not necessary; may be absent
 * ]
 *
 * @package rollun\amazonDropship\Amazon\Client\Factory
 */
class AmazonOrderToMegaplanDealTaskFactory implements FactoryInterface
{
    const ORDER_CLIENT_KEY = 'taskAmazonOrderToMegaplanDeal';
    // TODO: When/if more amazon services will be process the next constant will be move to abstract factory for these services
    const ORDER_CLIENT_CLASS_KEY = 'class';
    const ORDER_CLIENT_CONFIG_SECTION_KEY = 'config_section';
    const ORDER_CLIENT_PATH_TO_CONFIG_KEY = 'path_to_config';
    const MEGAPLAN_DATASTORE_ASPECT_KEY = 'megaplan_dataStore';
    const TRACKING_NUMBER_DATASTORE_KEY = 'tracking_number_dataStore';
    const LOGGER_KEY = 'logger';

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

        $megaplanDatStore = $container->get($serviceConfig[static::MEGAPLAN_DATASTORE_ASPECT_KEY]);
        $trackingNumberDataStore = $container->get($serviceConfig[static::TRACKING_NUMBER_DATASTORE_KEY]);
        if (isset($serviceConfig[static::LOGGER_KEY])) {
            $logger = $container->get($serviceConfig[static::LOGGER_KEY]);
        } else {
            $logger = null;
        }

        $instance = new $serviceConfig[static::ORDER_CLIENT_CLASS_KEY](
            $amazonOrderList,
            $megaplanDatStore,
            $trackingNumberDataStore,
            $logger
        );
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
            !isset($serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY]) ||
            !isset($serviceConfig[static::MEGAPLAN_DATASTORE_ASPECT_KEY]) ||
            !isset($serviceConfig[static::TRACKING_NUMBER_DATASTORE_KEY])
        ) {
            throw new ServiceNotCreatedException("Can't create service because its config has wrong format.");
        }
        $path = $serviceConfig[static::ORDER_CLIENT_PATH_TO_CONFIG_KEY];
        if (!file_exists($path) || !is_readable($path)) {
            throw new ServiceNotCreatedException("Amazon config file with specified path doesn't exist");
        }

        return $serviceConfig;
    }
}