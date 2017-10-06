<?php

namespace rollun\application\App\Amazon;

use rollun\application\App\Amazon\Client\Factory\OrderClientFactory;
use rollun\installer\Command;
use rollun\application\App\Amazon\Client\OrderClient;

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
            OrderClientFactory::ORDER_CLIENT_KEY => $this->getAmazonOrderClient(),
            'callback' => $this->getCallback(),
            'interrupt' => $this->getInterrupt(),
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
                OrderClient::class => OrderClientFactory::class,
            ],
            'aliases' => [
                OrderClientFactory::ORDER_CLIENT_KEY => OrderClient::class,
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
            OrderClientFactory::ORDER_CLIENT_KEY => [
                OrderClientFactory::ORDER_CLIENT_CLASS_KEY => OrderClient::class,
                OrderClientFactory::ORDER_CLIENT_CONFIG_SECTION_KEY => "SaaS2Amazon",
                OrderClientFactory::ORDER_CLIENT_PATH_TO_CONFIG_KEY => Command::getDataDir() .
                    "amazon/client/amazon-config.php",
            ]
        ];
    }

    public function getCallback()
    {
        return [
            'hourly_multiplexer' => [
                'class' => 'rollun\callback\Callback\Multiplexer',
                'interrupters' => [
                    OrderClient::class,
                ],
            ],
        ];
    }

    public function getInterrupt()
    {
        return [
            'cron' => [
                'class' => 'rollun\callback\Callback\Interruptor\Process',
                'callbackService' => 'hourly_multiplexer',
            ],
//            'interrupt_cron_sec_ticker' => [
//                'class' => 'rollun\callback\Callback\Interruptor\Process',
//                'callbackService' => 'cron_sec_ticker',
//            ],
//            'interrupt_sec_multiplexer' => [
//                'class' => 'rollun\callback\Callback\Interruptor\Process',
//                'callbackService' => 'sec_multiplexer',
//            ],
        ];
    }
}