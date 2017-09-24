<?php

namespace rollun\test\application\App\Megaplan\Aspect;

use Megaplan\SimpleClient\Client;
use Mockery;
use rollun\api\Api\Megaplan\Serializer\MegaplanSerializer;
use rollun\datastore\DataStore\Aspect\AspectAbstract;
use rollun\application\App\Megaplan\Aspect\Deal;
use rollun\api\Api\Megaplan\DataStore\MegaplanDataStore;
use Interop\Container\ContainerInterface;
use rollun\api\Api\Megaplan\Exception\InvalidArgumentException;

class DealTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface */
    protected $container;

    protected $itemDataDummy = [
        'amazon_order_id' => null,
        'payments_date' => null, // [UTC timestamp]
        'merchant_order_id' => null,
        'tracking_number' => null,
    ];

    protected function setUp()
    {
        $this->getContainerMock();
    }

    protected function getContainerMock($dealsCountWithTheSameOrderId = 1)
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')
            ->andReturn(true);
        $container->shouldReceive('get')
            ->andReturnUsing(function($requestedName) use ($container, $dealsCountWithTheSameOrderId) {
                switch ($requestedName) {
                    case 'megaplanClient':
                        $instance = Mockery::mock(Client::class);
                        break;
                    case 'serializer':
                        $instance = Mockery::mock(MegaplanSerializer::class);
                        $instance->shouldReceive('getOptions')
                            ->andReturn(true);
                        break;
                    case 'megaplan_deal_dataStore_service':
                        $instance = Mockery::mock(MegaplanDataStore::class);
                        $instance->shouldReceive('query')
                            ->andReturnUsing(function() use ($dealsCountWithTheSameOrderId) {
                                $return = [];
                                for ($i = 0; $i < $dealsCountWithTheSameOrderId; $i++) {
                                    $return[] = [
                                        'Id' => 100 + $i,
                                    ];
                                }
                                return $return;
                            });
                        $instance->shouldReceive('create')
                            ->andReturnUsing(function($itemData, $rewriteIfExist = false) {
                                return $itemData;
                            });
                        break;
                    case 'megaplan_dataStore_aspect':
                        $instance = new Deal($container->get('megaplan_deal_dataStore_service'));
                        break;
                    default:
                        throw new \Zend\ServiceManager\Exception\InvalidArgumentException("Can't to create a service with name \"{$requestedName}\" because I'm Mock!!");
                        break;
                }
                return $instance;
            });
        \rollun\dic\InsideConstruct::setContainer($container);
        $this->container = $container;
    }

    /**
     * Real creation an aspect
     *
     * @return AspectAbstract
     */
    public function test_instanceCreation_shouldReturnAspectObject()
    {
        global $container;
        /** @var AspectAbstract $dataStore */
        $dataStore = $container->get('megaplan_dataStore_aspect');
        $this->assertInstanceOf(
            Deal::class, $dataStore
        );
        return $dataStore;
    }

//    /**
//     * @depends test_instanceCreation_shouldReturnAspectObject
//     * @param AspectAbstract $dataStore
//     */
//    public function test_megaplanCreateNewEntity_shouldReturnCreatedEntityAsArray(AspectAbstract $dataStore)
//    {
//        $this->itemDataDummy['amazon_order_id'] = '111-6280736-9203424-test';
//        $this->itemDataDummy['payments_date'] = '1504652400';
//        $this->itemDataDummy['merchant_order_id'] = '111-6280736-9203424-test';
//        $this->itemDataDummy['tracking_number'] = '7357852521410000';
//
//        $deal = $dataStore->create($this->itemDataDummy, true);
//
//        $this->assertTrue(
//            is_array($deal)
//        );
//        $this->assertArrayHasKey(
//            'Id', $deal
//        );
//        $this->assertInternalType(
//            'numeric', $deal['Id']
//        );
//    }

    public function test_preCreateDealMethod_shouldReturnChangedItemData()
    {
        $this->itemDataDummy['amazon_order_id'] = '111-6280736-9203424-test';
        $this->itemDataDummy['payments_date'] = '1504652400';
        $this->itemDataDummy['merchant_order_id'] = '111-6280736-9203424-test';
        $this->itemDataDummy['tracking_number'] = '7357852521410000';


        $dealAspect = $this->container->get('megaplan_dataStore_aspect');
        $itemData = $dealAspect->create($this->itemDataDummy);

        $this->assertArrayHasKey(
            'Id', $itemData
        );
        $this->assertEquals(
            100, $itemData['Id']
        );
        $this->assertArrayHasKey(
            'Model', $itemData
        );
        $this->assertArrayHasKey(
            'Model', $itemData
        );
        $this->assertArrayHasKey(
            'Category1000060CustomFieldOrderId', $itemData['Model']
        );
        $this->assertEquals(
            '111-6280736-9203424-test', $itemData['Model']['Category1000060CustomFieldOrderId']
        );

        $this->assertArrayHasKey(
            'Category1000060CustomFieldDataZakaza', $itemData['Model']
        );
        $this->assertEquals(
            '2017-09-06', $itemData['Model']['Category1000060CustomFieldDataZakaza']
        );

        $this->assertArrayHasKey(
            'Category1000060CustomFieldNomerZakazaUPostavshchika', $itemData['Model']
        );
        $this->assertEquals(
            '111-6280736-9203424-test', $itemData['Model']['Category1000060CustomFieldNomerZakazaUPostavshchika']
        );

        $this->assertArrayHasKey(
            'Category1000060CustomFieldTrekNomer', $itemData['Model']
        );
        $this->assertEquals(
            '7357852521410000', $itemData['Model']['Category1000060CustomFieldTrekNomer']
        );
    }

    public function test_preCreateDealMethod_checkRequiredParams_correctValues_shouldReturnChangedItemData()
    {
        $this->itemDataDummy['amazon_order_id'] = '111-6280736-9203424-test';
        $this->itemDataDummy['payments_date'] = '1504652400';

        $dealAspect = $this->container->get('megaplan_dataStore_aspect');
        $itemData = $dealAspect->create($this->itemDataDummy);
        // Just checks if the method returns something (doesn't throw an exception)
        $this->assertArrayHasKey(
            'Id', $itemData
        );
    }

    public function test_preCreateDealMethod_checkRequiredParams_orderIdAbsents_shouldThrowException()
    {
        $dealAspect = $this->container->get('megaplan_dataStore_aspect');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Format of specified data is wrong");

        $dealAspect->create($this->itemDataDummy);
    }

    public function test_preCreateDealMethod_checkRequiredParams_paymentDateAbsents_shouldThrowException()
    {
        $this->itemDataDummy['amazon_order_id'] = '111-6280736-9203424-test';

        $dealAspect = $this->container->get('megaplan_dataStore_aspect');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Format of specified data is wrong");

        $dealAspect->create($this->itemDataDummy);
    }

    public function test_preCreateDealMethod_checkRequiredParams_lessThanThreeParams_shouldThrowException()
    {
        unset($this->itemDataDummy['tracking_number']);
        $dealAspect = $this->container->get('megaplan_dataStore_aspect');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Format of specified data is wrong");

        $dealAspect->create($this->itemDataDummy);
    }

    public function test_preCreateDealMethod_checkRequiredParams_moreThanFiveParams_shouldThrowException()
    {
        $this->itemDataDummy['dummy'] = null;
        $dealAspect = $this->container->get('megaplan_dataStore_aspect');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Format of specified data is wrong");

        $dealAspect->create($this->itemDataDummy);
    }

    public function test_preCreateDealMethod_moreThanOneDealWithTheSameOrderIdOnMegaplan_shouldThrowException()
    {
        $this->itemDataDummy['amazon_order_id'] = '111-6280736-9203424-test';
        $this->itemDataDummy['payments_date'] = '1504652400';

        $dealsCount = 2;
        $this->getContainerMock($dealsCount);
        $dealAspect = $this->container->get('megaplan_dataStore_aspect');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There are \"" . $dealsCount
            . "\" entities with OrderID=\"111-6280736-9203424-test\"");

        $dealAspect->create($this->itemDataDummy);
    }

    public function test_updateMethod_shouldExecuteCreateMethod()
    {
        $this->itemDataDummy['amazon_order_id'] = '111-6280736-9203424-test';
        $this->itemDataDummy['payments_date'] = '1504652400';
        $this->itemDataDummy['merchant_order_id'] = '111-6280736-9203424-test';
        $this->itemDataDummy['tracking_number'] = '7357852521410000';

        $dealAspect = $this->container->get('megaplan_dataStore_aspect');
        // Execute "update" but in the fact "create" will be executed
        $itemData = $dealAspect->update($this->itemDataDummy);
        // Just checks if the method returns something (doesn't throw an exception etc)
        $this->assertArrayHasKey(
            'Id', $itemData
        );
    }
}