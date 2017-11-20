<?php

namespace rollun\test\amazonDropship\Amazon\Client;

use Interop\Container\ContainerInterface;
use rollun\amazonDropship\Client\AmazonOrderToMegaplanDealTask;
use rollun\amazonDropship\Client\Factory\AmazonOrderToMegaplanDealTaskFactory;
use \AmazonOrder;

class AmazonOrderToMegaplanDealTaskTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ContainerInterface */
    protected $container;
    /** @var AmazonOrderToMegaplanDealTask */
    protected $orderClient;

    protected function setUp()
    {
        global $container;
        $this->container = $container;

        $this->orderClient = $this->container->get(AmazonOrderToMegaplanDealTaskFactory::ORDER_CLIENT_KEY);
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
        $params = [
            'mode' => 'Modified',
        ];
        $orderList = $this->orderClient->getOrderList($params);

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
        return $order;
    }

    /**
     * @depends test_getOrderList_shouldReturnOrderList
     * @param AmazonOrder $order
     */
    public function test_getTrackingNumber_shouldReturnDatStoreItem(AmazonOrder $order)
    {
        $trackingNumbers = [
            [
                'invoice_number' => '111-6280736-9203424',
                'tracking_number' => '11863460621178',
            ],
            [
                'invoice_number' => '112-0493542-9445047',
                'tracking_number' => '6197926627930',
            ],
            [
                'invoice_number' => '114-1765421-6237853',
                'tracking_number' => '27244596269114',
            ],
            [
                'invoice_number' => '113-0219829-6051462',
                'tracking_number' => '9751398520618',
            ],
        ];
        $trackingNumberDataStore = $this->container->get('tracking_number_dataStore');
        foreach($trackingNumbers as $number) {
            $trackingNumberDataStore->create($number);
        }

        foreach($trackingNumbers as $number) {
            $trackingNumber = $this->orderClient->getTrackingNumber($number['invoice_number']);
            $this->assertEquals(
                $number['tracking_number'], $trackingNumber
            );
        }
    }
}