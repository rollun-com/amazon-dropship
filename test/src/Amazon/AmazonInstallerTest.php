<?php

namespace rollun\test\amazonDropship\Amazon;

use rollun\amazonDropship\Callback\AmazonOrderTaskCallback;
use rollun\amazonDropship\Client\Factory\AmazonOrderListFactory;
use rollun\installer\Command;
use rollun\installer\TestCase\InstallerTestCase;
use rollun\amazonDropship\AmazonOrderInstaller;

class AmazonInstallerTest extends InstallerTestCase
{
    protected $config;

    protected function setUp()
    {
        // 7 parameters with default value
        $io = $this->getIo("\n\n\n\n\n\n\n", $this->getOutputStream());
        $installer = new AmazonOrderInstaller($this->getContainer(), $io);
        $this->config = $installer->install();
    }

    public function test_install_shouldCheckDataStoreSection()
    {
        $this->assertArrayHasKey(
            'dataStore', $this->config
        );
    }

    /**
     * @ depends test_install_shouldCheckDataStoreSection
     */
    public function test_install_shouldCheckAmazonOrderTaskCallbackSection()
    {
        $this->assertArrayHasKey(
            AmazonOrderTaskCallback::class, $this->config
        );

        $config = $this->config[AmazonOrderTaskCallback::class];

        $this->assertEquals(
            'Modified', $config['mode']
        );

        $this->assertEquals(
            '-1 Hour', $config['since_datetime']
        );

        $this->assertEquals(
            null, $config['till_datetime']
        );

        $this->assertEquals(
            ['*'], $config['schedule']['hours']
        );

        $this->assertEquals(
            [0], $config['schedule']['minutes']
        );
    }

    public function test_install_shouldCheckAmazonOrderListSection()
    {
        $this->assertArrayHasKey(
            AmazonOrderListFactory::AMAZON_ORDER_LIST_KEY, $this->config
        );

        $config = $this->config[AmazonOrderListFactory::AMAZON_ORDER_LIST_KEY];

        $this->assertEquals(
            'SaaS2Amazon', $config[AmazonOrderListFactory::ORDER_CLIENT_CONFIG_SECTION_KEY]
        );

        $this->assertEquals(
            Command::getDataDir() . "amazon/client/amazon-config.php",
            $config[AmazonOrderListFactory::ORDER_CLIENT_PATH_TO_CONFIG_KEY]
        );
    }
}