<?php

namespace rollun\amazonDropship\Amazon;

use rollun\amazonDropship\Amazon\Client\Factory\AmazonOrderToMegaplanDealTaskFactory;
use rollun\datastore\DataStore\Memory;
use rollun\installer\Command;
use rollun\amazonDropship\Amazon\Client\AmazonOrderToMegaplanDealTask;

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
            AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_KEY => $this->getAmazonOrderClient(),
            'callback' => $this->getCallback(),
            'interrupt' => $this->getInterrupt(),
            'dataStore' => [
                'tracking_number_dataStore' => [
                    'class' => Memory::class,
                ],
            ],
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
                AmazonOrderToMegaplanDealTask::class => AmazonOrderToMegaplanDealTaskFactory::class,
            ],
            'aliases' => [
                AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_KEY => AmazonOrderToMegaplanDealTask::class,
            ],
        ];
    }

    /**
     * Returns Amazon access parameters
     *
     * @return array
     */
    public function getAmazonOrderClient()
    {
        return [
            AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_KEY => [
                AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_CLASS_KEY => AmazonOrderToMegaplanDealTask::class,
                AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_CONFIG_SECTION_KEY => "SaaS2Amazon",
                AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_PATH_TO_CONFIG_KEY => Command::getDataDir() .
                    "amazon/client/amazon-config.php",
                AmazonOrderToMegaplanDealTaskFactory::MEGAPLAN_DATASTORE_ASPECT_KEY => 'megaplan_dataStore_aspect',
                AmazonOrderToMegaplanDealTaskFactory::TRACKING_NUMBER_DATASTORE_KEY => 'tracking_number_dataStore',
            ]
        ];
    }

    /**
     * Returns cron hourly task config
     *
     * @return array
     */
    public function getCallback()
    {
        return [
            'hourly_multiplexer' => [
                'class' => 'rollun\callback\Callback\Multiplexer',
                'interrupters' => [
                    AmazonOrderToMegaplanDealTask::class,
                ],
            ],
        ];
    }

    /**
     * Returns cron interrupter config
     *
     * @return array
     */
    public function getInterrupt()
    {
        return [
            'cron' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'hourly_multiplexer',
            ],
        ];
    }
}