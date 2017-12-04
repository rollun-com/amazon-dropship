<?php

namespace rollun\amazonDropship;

use rollun\amazonDropship\Client\Factory\AmazonOrderListFactory;
use rollun\amazonDropship\Client\Factory\AmazonOrderToMegaplanDealTaskFactory;
use rollun\amazonDropship\Client\AmazonOrderToMegaplanDealTask;
use rollun\logger\Logger;
use rollun\amazonDropship\Callback\AmazonOrderTaskCallback;

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
            AmazonOrderTaskCallback::class => [
                'callback' => 'taskAmazonOrder',
            ]
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
            'aliases' => [
                AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_KEY => AmazonOrderToMegaplanDealTask::class,
                'taskAmazonOrder' => AmazonOrderToMegaplanDealTask::class,
                'amazonOrderList' => AmazonOrderListFactory::AMAZON_ORDER_LIST_KEY,
                'megaplanDataStore' => 'megaplan_dataStore_aspect',
                'trackingNumberDataStore' => 'tracking_number_dataStore',
                'logger' => Logger::class,
                'amazonOrderTaskCallback' => AmazonOrderTaskCallback::class,
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
                AmazonOrderToMegaplanDealTaskFactory::MEGAPLAN_DATASTORE_ASPECT_KEY => 'megaplan_dataStore_aspect',
                AmazonOrderToMegaplanDealTaskFactory::TRACKING_NUMBER_DATASTORE_KEY => 'tracking_number_dataStore',
                'logger' => Logger::class,
            ],
        ];
    }
}