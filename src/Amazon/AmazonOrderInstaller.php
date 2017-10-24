<?php

namespace rollun\amazonDropship\Amazon;

use rollun\amazonDropship\Amazon\Callback\AmazonOrderTaskCallback;
use rollun\amazonDropship\Amazon\Callback\Factory\AmazonOrderTaskCallbackFactory;
use rollun\amazonDropship\Amazon\Client\AmazonOrderToMegaplanDealTask;
use rollun\amazonDropship\Amazon\Client\Factory\AmazonOrderListFactory;
use rollun\amazonDropship\Amazon\Client\Factory\AmazonOrderToMegaplanDealTaskFactory;
use rollun\datastore\DataStore\Memory;
use rollun\installer\Command;
use rollun\installer\Install\InstallerAbstract;

class AmazonOrderInstaller extends InstallerAbstract
{
    protected $message = 'The constant "APP_ENV" is not defined or its value is not "dev".
        You can\'t do anything in a non-DEV mode.';

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
                'dataStore' => $this->installDataStoreSection(),
                AmazonOrderTaskCallback::class => $this->installAmazonOrderTaskCallback(),
                AmazonOrderListFactory::AMAZON_ORDER_LIST_KEY => $this->installAmazonOrderList(),
                'dependencies' => [
                    'factories'  => [
                        AmazonOrderToMegaplanDealTask::class => AmazonOrderToMegaplanDealTaskFactory::class,
                        AmazonOrderTaskCallback::class => AmazonOrderTaskCallbackFactory::class,
                        AmazonOrderListFactory::AMAZON_ORDER_LIST_KEY => AmazonOrderListFactory::class,
                    ],
                ],
            ];
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
        // TODO: Implement uninstall() method.
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getDescription($lang = "en")
    {
        // TODO: Implement getDescription() method.
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (isset($config[AmazonOrderTaskCallback::class]) && isset($config[AmazonOrderListFactory::AMAZON_ORDER_LIST_KEY]));
    }

    protected function installDataStoreSection()
    {
        $config = [
            'tracking_number_dataStore' => [
                'class' => Memory::class,
            ],
        ];
        $this->consoleIO->write("The <info>Memory DataStore</info> was set by default for the <info>'tracking_number_dataStore'</info>.
            You can change this value in the config file later.");
        return $config;
    }

    protected function installAmazonOrderTaskCallback()
    {
        $modeCases = ['Created', 'Modified',];
        $config = [
            'mode' => 'Modified',
            'since_datetime' => '-1 Hour',
            'till_datetime' => null,
            'schedule' => [
                'hours' => '*',
                'minutes' => 0,
            ],
        ];

        do {
            $mode = $this->consoleIO->ask(
                "By what field do you want to receive Amazon orders (" . join("|", $modeCases) . ")]?",
                $config['mode']
            );
            if (!in_array($mode, $modeCases)) {
                $this->consoleIO->write("Specified value is wrong. The value has to be [" . join(", ", $modeCases) . "]");
            }
        } while (!in_array($mode, $modeCases));
        $config['mode'] = $mode;

        $config['since_datetime'] = $this->consoleIO->ask(
            "Since what time do you want to receive Amazon orders?",
            $config['since_datetime']
        );

        $config['till_datetime'] = $this->consoleIO->ask(
            "Till what time do you want to receive Amazon orders (null - till now)?",
            $config['till_datetime']
        );

        $config['schedule']['hours'] = [$this->consoleIO->ask(
            "Set the task start hour(-s) (separated by a comma; * - every)",
            $config['schedule']['hours']
        )];

        $config['schedule']['minutes'] = [$this->consoleIO->ask(
            "Set the task start minute(-s) of an hour (separated by a comma; * - every)",
            $config['schedule']['minutes']
        )];

        return $config;
    }

    protected function installAmazonOrderList()
    {
        $config = [
            AmazonOrderListFactory::ORDER_CLIENT_CONFIG_SECTION_KEY => "SaaS2Amazon",
            AmazonOrderListFactory::ORDER_CLIENT_PATH_TO_CONFIG_KEY => Command::getDataDir() .
                "amazon/client/amazon-config.php",
        ];

        $config[AmazonOrderListFactory::ORDER_CLIENT_CONFIG_SECTION_KEY] = $this->consoleIO->ask(
            "Set a section of the Amazon config where credentials are stored",
            $config[AmazonOrderListFactory::ORDER_CLIENT_CONFIG_SECTION_KEY]
        );

        $config[AmazonOrderListFactory::ORDER_CLIENT_PATH_TO_CONFIG_KEY] = $this->consoleIO->ask(
            "Set the path to the Amazon config file",
            $config[AmazonOrderListFactory::ORDER_CLIENT_PATH_TO_CONFIG_KEY]
        );

        return $config;
    }
}