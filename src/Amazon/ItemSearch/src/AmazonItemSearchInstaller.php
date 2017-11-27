<?php

namespace rollun\amazonItemSearch;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\amazonItemSearch\Client\Factory\AmazonSearchOperationFactory;
use rollun\amazonItemSearch\Client\Factory\ApaiIOFactory;
use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\TableGateway\Factory\TableGatewayAbstractFactory;
use rollun\installer\Command;
use rollun\installer\Install\InstallerAbstract;
use rollun\amazonItemSearch\Callback\AmazonItemSearchTaskCallback;

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
                    'country' => '',
                    'access_key' => '',
                    'secret_key' => '',
                    'associate_tag' => '',
                ],

                AmazonSearchOperationFactory::AMAZON_SEARCH_OPERATION_KEY => [
                    AmazonSearchOperationFactory::MINIMUM_PRICE_KEY => 0,
                    AmazonSearchOperationFactory::MAXIMUM_PRICE_KEY => 10000,
                ],

                TableGatewayAbstractFactory::KEY_TABLE_GATEWAY => [
                    'item_search_result' => [],
                ],
                'db' => [
                    'adapters' => [
                        'Zend\Db\Adapter\AdapterInterface' => [
                            'driver' => 'Pdo_Mysql',
                            "host" => "localhost",
                            'database' => '',
                            'username' => '',
                            'password' => '',
                        ],
                    ],
                ],
            ];

            $this->consoleIO->write("<info>You have to override credentials</info>. Also you can override a <info>prices range: minimum and maximum prices</info>."
                . PHP_EOL
                . "If you don't want to use the one <info>you can delete one of them or both</info>" . PHP_EOL
                . "<error>Pay attention: the price value has to be an integer value - without cents (real price * 100)</error>");

            return $config;
        }
    }

    public function uninstall()
    {
        if (is_file($this->brandSourceDataStoreFilename)) {
            unlink($this->brandSourceDataStoreFilename);
        }
    }

    public function getDescription($lang = "en")
    {
        return "Sets and allows to use a library for item searching on the Amazon via Product Advertising API";
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (isset($config[AmazonItemSearchTaskCallback::class]));
    }

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
                DbTableAbstractFactory::KEY_TABLE_GATEWAY => "item_search_result",
            ],
        ];
    }

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
}