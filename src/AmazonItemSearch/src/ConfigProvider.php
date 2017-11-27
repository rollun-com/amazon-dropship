<?php

namespace rollun\amazonItemSearch;

use rollun\amazonItemSearch\Callback\AmazonItemSearchTaskCallback;
use rollun\amazonItemSearch\Callback\Factory\AmazonItemSearchTaskCallbackFactory;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\datastore\DataStore\Memory;
use ApaiIO\ApaiIO;
use rollun\amazonItemSearch\Client\Factory\ApaiIOFactory;
use ApaiIO\Operations\Search;
use rollun\amazonItemSearch\Client\Factory\AmazonSearchOperationFactory;
use rollun\amazonItemSearch\Client\AmazonItemSearchTask;

/**
 * The configuration provider for the Amazon module
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
            'callback' => $this->getCallback(),
            'interrupt' => $this->getInterrupt(),

            AmazonSearchOperationFactory::AMAZON_SEARCH_OPERATION_KEY => [
                AmazonSearchOperationFactory::RESPONSE_GROUP_KEY => [
                    'SalesRank',
                    'OfferFull',
                    'Large',
                ],
            ],
            AmazonItemSearchTaskCallback::class => [
                'callback' => 'taskAmazonItemSearch',
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
                AmazonItemSearchTask::class => AmazonItemSearchTask::class,
            ],
            'factories'  => [
                AmazonItemSearchTaskCallback::class => AmazonItemSearchTaskCallbackFactory::class,
                ApaiIO::class => ApaiIOFactory::class,
                Search::class => AmazonSearchOperationFactory::class,
            ],
            'aliases' => [
                'taskAmazonItemSearch' => AmazonItemSearchTask::class,
                'taskAmazonItemSearchCallback' => AmazonItemSearchTaskCallback::class,
                'brandSourceDataStore' => 'brand_source_dataStore',
                'temporaryDataStore' => 'temporary_dataStore',
                'itemSearchResultDataStore' => 'result_dataStore',
                'amazonProductAdvertisingApiClient' => ApaiIO::class,
                'amazonSearchOperation' => Search::class,
            ],
        ];
    }

    public function getDataStore()
    {
        return [
            'temporary_dataStore' => [
                'class' => Memory::class,
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
            'min_multiplexer' => [
                'class' => 'rollun\callback\Callback\Multiplexer',
                'interrupters' => [
                    'hourly_ticker_interrupter',
                ],
            ],
            'cron_hourly_ticker' => [
                'class' => 'rollun\callback\Callback\Ticker',
                TickerAbstractFactory::KEY_TICKS_COUNT => 1,
                TickerAbstractFactory::KEY_DELAY_MC => 0, // execute right away
                'callback' => 'hourly_multiplexer_interrupter',
            ],
            'hourly_multiplexer' => [
                'class' => 'rollun\callback\Callback\Multiplexer',
                'interrupters' => [
                    'AmazoItemSearchTask_interrupter',
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
            'hourly_ticker_interrupter' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'cron_hourly_ticker',
            ],
            'hourly_multiplexer_interrupter' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'hourly_multiplexer',
            ],
            'AmazoItemSearchTask_interrupter' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'taskAmazonItemSearchCallback',
            ],
        ];
    }
}
