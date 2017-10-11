<?php

namespace rollun\amazonDropship\Megaplan;

/**
 * The configuration provider for the App module
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
            'megaplan_dataStore_aspect' => [
                'class' => 'rollun\amazonDropship\Megaplan\Aspect\Deal',
                'dataStore' => 'megaplan_deal_dataStore_service',
            ],
        ];
    }
}
