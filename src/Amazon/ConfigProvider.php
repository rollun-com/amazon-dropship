<?php

namespace rollun\amazonDropship\Amazon;

use AmazonOrderList;
use rollun\amazonDropship\Amazon\Client\Factory\AmazonOrderListFactory;
use rollun\amazonDropship\Amazon\Client\Factory\AmazonOrderToMegaplanDealTaskFactory;
use rollun\datastore\DataStore\CsvBase;
use rollun\installer\Command;
use rollun\amazonDropship\Amazon\Client\AmazonOrderToMegaplanDealTask;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\logger\Logger;
use rollun\amazonDropship\Amazon\Callback\AmazonOrderTaskCallback;
use rollun\amazonDropship\Amazon\Callback\Factory\AmazonOrderTaskCallbackFactory;

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

    /**
     * Returns cron hourly task config
     *
     * 'min_multiplexer' => 'hourly_ticker_interrupter' => 'cron_hourly_ticker'
     *      => 'hourly_multiplexer_interrupter' => 'hourly_multiplexer' => 'AmazonOrderToMegaplanDealTask_interrupter'
     *
     * @return array
     */
    public function getCallback()
    {
        return [
            'hourly_multiplexer' => [
                'class' => 'rollun\callback\Callback\Multiplexer',
                'interrupters' => [
                    'AmazonOrderToMegaplanDealTask_interrupter',
                ],
            ],
            'cron_hourly_ticker' => [
                'class' => 'rollun\callback\Callback\Ticker',
                TickerAbstractFactory::KEY_TICKS_COUNT => 1,
                TickerAbstractFactory::KEY_DELAY_MC => 0, // execute right away
                'callback' => 'hourly_multiplexer_interrupter',
            ],
            'min_multiplexer' => [
                'class' => 'rollun\callback\Callback\Multiplexer',
                'interrupters' => [
                    'hourly_ticker_interrupter',
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
            'AmazonOrderToMegaplanDealTask_interrupter' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'amazonOrderTaskCallback',
            ],
            'hourly_multiplexer_interrupter' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'hourly_multiplexer',
            ],
            'hourly_ticker_interrupter' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'cron_hourly_ticker',
            ],
        ];
    }
}