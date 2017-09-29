<?php

namespace rollun\test\application\App\Amazon\Client;

use rollun\application\App\Amazon\Client\OrderClient;
use rollun\application\App\Amazon\Client\Factory\OrderClientFactory;
use \AmazonOrder;

class OrderClientTest extends \PHPUnit_Framework_TestCase
{
    protected $container;
    /** @var OrderClient */
    protected $orderClient;

    protected function setUp()
    {
        global $container;
        $this->orderClient = $container->get(OrderClientFactory::ORDER_CLIENT_KEY);
        /*
         * The function \AmazonCore::fetchMockFile includes absolute path to file only if its path begins from '..' or '/'.
         * Otherwise this method expects that the mock file is located in the project root folder in the 'mock' sub folder.
         * That's why I'm made to build such a difficult path to file.
         * I get basename of the project folder, then go to '..' and return to this basename. And then add the rest path.
         *
         * @see \AmazonCore::fetchMockFile
         */
        $this->orderClient->setMock(true,
            '..' . DIRECTORY_SEPARATOR . basename(getcwd()) . DIRECTORY_SEPARATOR   // this is the project root folder
            . 'test' . DIRECTORY_SEPARATOR
            . 'data' . DIRECTORY_SEPARATOR . 'amazon.orders.xml');
    }

    public function test_getOrderList_shouldReturnOrderList()
    {
        $orderList = $this->orderClient->getOrderList();

        $this->assertTrue(
            is_array($orderList)
        );

        $this->assertEquals(
            1, count($orderList)
        );

        /** @var AmazonOrder $order */
        $order = $orderList[0];

        $this->assertInstanceOf(
            AmazonOrder::class, $order
        );

        $this->assertEquals(
            '114-9270757-6876200', $order->getAmazonOrderId()
        );

        $this->assertEquals(
            '114-9270757-6876200', $order->getSellerOrderId()
        );

        $this->assertEquals(
            '2017-09-28T05:30:28Z', $order->getPurchaseDate()
        );

        $this->assertEquals(
            'Shipped', $order->getOrderStatus()
        );
    }
}