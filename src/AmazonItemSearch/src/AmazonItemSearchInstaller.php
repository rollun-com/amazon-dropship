<?php

namespace rollun\amazonItemSearch;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\amazonItemSearch\Client\Factory\AmazonSearchOperationFactory;
use rollun\amazonItemSearch\Client\Factory\ApaiIOFactory;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\installer\Command;
use rollun\installer\Install\InstallerAbstract;
use rollun\amazonItemSearch\Callback\AmazonItemSearchTaskCallback;
use rollun\callback\Callback\Factory\TickerAbstractFactory;

class AmazonItemSearchInstaller extends InstallerAbstract
{
    protected $message = 'The constant "APP_ENV" is not defined or its value is not "dev".
        You can\'t do anything in a non-DEV mode.';

    protected $brandSourceDataStoreFilename;

    public function __construct(ContainerInterface $container, IOInterface $ioComposer)
    {
        parent::__construct($container, $ioComposer);
        $this->brandSourceDataStoreFilename = Command::getDataDir() . "brandSource.csv";
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function install()
    {
        if (constant('APP_ENV') !== 'dev') {
            $this->consoleIO->write($this->message);
            return [];
        } else {
            $config = [
                'dataStore' => $this->getDataStore(),
                AmazonItemSearchTaskCallback::class => $this->installAmazonItemSearchTaskCallback(),

                ApaiIOFactory::APAIIO_KEY => [
                    ApaiIOFactory::COUNTRY_KEY => '',
                    ApaiIOFactory::ACCESS_KEY_KEY => '',
                    ApaiIOFactory::SECRET_KEY_KEY => '',
                    ApaiIOFactory::ASSOCIATE_TAG_KEY => '',
                ],

                AmazonSearchOperationFactory::AMAZON_SEARCH_OPERATION_KEY => [
                    AmazonSearchOperationFactory::MINIMUM_PRICE_KEY => 0,
                    AmazonSearchOperationFactory::MAXIMUM_PRICE_KEY => 10000,
                ],

                'db' => [
                    'adapters' => [
                        'amazon_item_search_connection' => [
                            'driver' => 'Pdo_Mysql',
                            "host" => "localhost",
                            'database' => '',
                            'username' => '',
                            'password' => '',
                        ],
                    ],
                ],
                'callback' => $this->getCallback(),
                'interrupt' => $this->getInterrupt(),
            ];

            $this->consoleIO->write("<info>You have to override credentials</info>. Also you can override a <info>prices range: minimum and maximum prices</info>."
                . PHP_EOL
                . "If you don't want to use the one <info>you can delete one of them or both</info>" . PHP_EOL
                . "<info>Pay attention: the price value has to be an integer value - without cents (real price * 100)</info>");

            return $config;
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function uninstall()
    {
        if (is_file($this->brandSourceDataStoreFilename)) {
            unlink($this->brandSourceDataStoreFilename);
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getDescription($lang = "en")
    {
        return "Sets and allows to use a library for item searching on the Amazon via Product Advertising API";
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function isInstall()
    {
        $config = $this->container->get('config');
        return (isset($config[ApaiIOFactory::APAIIO_KEY]));
    }

    /**
     * Gets dataStores which can be overrided in the local config file
     *
     * Also creates a dummy for the BrandSourceDataStore
     *
     * @return array
     */
    public function getDataStore()
    {
        file_put_contents($this->brandSourceDataStoreFilename, "id;brand;category");
        return [
            'brand_source_dataStore' => [
                'class' => CsvBase::class,
                'filename' => $this->brandSourceDataStoreFilename,
                'delimiter' => ";",
            ],
            'result_dataStore' => [
                "class" => DbTable::class,
                DbTableAbstractFactory::KEY_TABLE_NAME => "",
                DbTableAbstractFactory::KEY_DB_ADAPTER => "",
            ],
        ];
    }

    /**
     * Sets a schedule for the task's callback
     *
     * @return array
     */
    protected function installAmazonItemSearchTaskCallback()
    {
        $config = [
            'schedule' => [
                'hours' => '*',
                'minutes' => 15,
            ],
        ];

        $config['schedule']['hours'] = [$this->consoleIO->ask(
            "Set the task start hour(-s) (separated by a comma; * - every) [{$config['schedule']['hours']}]:",
            $config['schedule']['hours']
        )];

        $config['schedule']['minutes'] = [$this->consoleIO->ask(
            "Set the task start minute(-s) of an hour (separated by a comma; * - every) [{$config['schedule']['minutes']}]:",
            $config['schedule']['minutes']
        )];

        return $config;
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