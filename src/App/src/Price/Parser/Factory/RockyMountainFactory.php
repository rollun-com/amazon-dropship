<?php

namespace rollun\application\App\Price\Parser\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use rollun\application\App\Price\Parser\RockyMountain;

/**
 * Class RockyMountainFactory
 *
 * 'price_parser' => [
 *     'rocky_mountain' => [
 *         'filename' => 'real_path_to_priceList_file',
 *         'dataStore' => 'real_dataStore_service_name',
 *     ],
 * ],
 *
 * @package rollun\application\App\Price\Parser\Factory
 */
class RockyMountainFactory implements FactoryInterface
{
    const PRICE_PARSER_KEY = 'price_parser';
    const ROCKY_MOUNTAIN_KEY = 'rocky_mountain';
    const PRICE_LIST_FILE_NAME_KEY = 'filename';
    const PRICE_LIST_PARSER_DATASTORE_KEY = 'dataStore';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config[static::PRICE_PARSER_KEY][static::ROCKY_MOUNTAIN_KEY])) {
            throw new ServiceNotFoundException("The configuration for price parser is not found");
        }
        $serviceConfig = $config[static::PRICE_PARSER_KEY][static::ROCKY_MOUNTAIN_KEY];

        $filename = $serviceConfig[static::PRICE_LIST_FILE_NAME_KEY];
        $dataStore = $container->get($serviceConfig[static::PRICE_LIST_PARSER_DATASTORE_KEY]);

        $instance = new RockyMountain($filename, $dataStore);
        return $instance;
    }

}