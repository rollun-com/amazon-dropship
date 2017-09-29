<?php

namespace rollun\test\application\App\Amazon\Client\Factory;

use Interop\Container\ContainerInterface;
use rollun\application\App\Amazon\Client\Factory\OrderClientFactory;
use rollun\application\App\Amazon\Client\OrderClient;

class OrderClientFactoryTest extends \PHPUnit_Framework_TestCase
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
        $instance = $this->container->get(OrderClientFactory::ORDER_CLIENT_KEY);
        $this->assertInstanceOf(
            OrderClient::class, $instance
        );
    }
}