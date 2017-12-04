<?php

namespace rollun\amazonItemSearch;

use rollun\amazonItemSearch\Callback\AmazonItemSearchTaskCallback;
use rollun\amazonItemSearch\Callback\Factory\AmazonItemSearchTaskCallbackFactory;
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
}
