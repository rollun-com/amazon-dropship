<?php

namespace rollun\test\amazonDropship\Amazon\Client\Factory;

use Interop\Container\ContainerInterface;
use rollun\amazonDropship\Client\Factory\AmazonOrderToMegaplanDealTaskFactory;
use rollun\amazonDropship\Client\AmazonOrderToMegaplanDealTask;

class AmazonOrderToMegaplanDealTaskFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface */
    protected $container;

    protected function setUp()
    {
        global $container;
        $this->container = $container;
    }

    public function test_orderClientFactory_shouldReturnCreatedInstance()
    {
        $instance = $this->container->get(AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_KEY);
        $this->assertInstanceOf(
            AmazonOrderToMegaplanDealTask::class, $instance
        );
    }
}