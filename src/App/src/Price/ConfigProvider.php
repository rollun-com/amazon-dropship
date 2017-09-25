<?php

namespace rollun\application\App\Price;

use rollun\datastore\DataStore\Memory;
use rollun\application\App\Price\Parser\RockyMountain;

/**
 * The configuration provider for the Price module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
            'dataStore' => $this->getDataStore(),
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'invokables' => [
            ],
            'factories'  => [
            ],
        ];
    }

    public function getDataStore()
    {
        return [
            'rockyMountain_price_dataStore' => [
                'class' => Memory::class,
            ],
            'rockyMountain_price_dataStore_aspect' => [
                'class' => RockyMountain::class,
                'dataStore' => 'rockyMountain_price_dataStore',
            ],
        ];
    }
}
