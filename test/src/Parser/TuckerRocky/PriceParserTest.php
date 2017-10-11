<?php

namespace rollun\test\parser\TuckerRocky;

use Interop\Container\ContainerInterface;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\parser\TuckerRocky\PriceParser;

class PriceParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface */
    protected $container;

    protected function setUp()
    {
        global $container;
        $this->container = $container;
    }

    public function test_factory_shouldReturnParserObject()
    {
        $parser = $this->container->get('rocky_mountain_price_parser');
        $this->assertInstanceOf(
            PriceParser::class, $parser
        );
        return $parser;
    }

    /**
     * @depends test_factory_shouldReturnParserObject
     * @param PriceParser $parser
     * @return PriceParser
     */
    public function test_parse_shouldSaveAllDataToDataStore(PriceParser $parser)
    {
        $parser();
        $this->assertTrue(true);
        return $parser;
    }

    /**
     * @depends test_parse_shouldSaveAllDataToDataStore
     * @param PriceParser $parser
     * @return \rollun\datastore\DataStore\Interfaces\DataStoresInterface
     */
    public function test_create_lastElementExists_shouldReturnParsedRow(PriceParser $parser)
    {
        $dataStore = $parser->getDataStore();
        // 373843 SN900080.1900080.1900114.56S9091917
        $item = $dataStore->read('373843');

        $this->assertArrayHasKey(
            'Item', $item
        );
        $this->assertEquals(
            '373843', $item['Item']
        );

        $this->assertArrayHasKey(
            'Status', $item
        );
        $this->assertEquals(
            '', $item['Status']
        );

        $this->assertArrayHasKey(
            'Availability', $item
        );
        $this->assertEquals(
            'Sufficient', $item['Availability']
        );

        $this->assertArrayHasKey(
            'In-Transit', $item
        );
        $this->assertEquals(
            'No', $item['In-Transit']
        );

        $this->assertArrayHasKey(
            'Inventory Qty', $item
        );
        $this->assertEquals(
            9, $item['Inventory Qty']
        );

        $this->assertArrayHasKey(
            'Standard Price', $item
        );
        $this->assertEquals(
            80.19, $item['Standard Price']
        );

        $this->assertArrayHasKey(
            'Best Price', $item
        );
        $this->assertEquals(
            80.19, $item['Best Price']
        );

        $this->assertArrayHasKey(
            'Retail Price', $item
        );
        $this->assertEquals(
            114.56, $item['Retail Price']
        );

        $this->assertArrayHasKey(
            'Retail Price', $item
        );
        $this->assertEquals(
            'Sufficient', $item['Drop Ship Availability']
        );

        $this->assertArrayHasKey(
            'Drop Ship Inventory Qty', $item
        );
        $this->assertEquals(
            9, $item['Drop Ship Inventory Qty']
        );

        $this->assertArrayHasKey(
            'Drop Ship Inventory Qty', $item
        );
        $this->assertEquals(
            '19-09-2017', $item['Next Expected PO Due Date']
        );

        return $dataStore;
    }

    /**
     * @depends test_create_lastElementExists_shouldReturnParsedRow
     * @param DataStoresInterface $dataStore
     */
    public function test_create_lastElementAbsents_shouldReturnParsedRow(DataStoresInterface $dataStore)
    {
        // BA0027DZN000084.9900084.9900194.95Z0
        $item = $dataStore->read('BA0027');

        $this->assertArrayHasKey(
            'Item', $item
        );
        $this->assertEquals(
            'BA0027', $item['Item']
        );

        $this->assertArrayHasKey(
            'Status', $item
        );
        $this->assertEquals(
            'Discontinued', $item['Status']
        );

        $this->assertArrayHasKey(
            'Availability', $item
        );
        $this->assertEquals(
            'Zero', $item['Availability']
        );

        $this->assertArrayHasKey(
            'In-Transit', $item
        );
        $this->assertEquals(
            'No', $item['In-Transit']
        );

        $this->assertArrayHasKey(
            'Inventory Qty', $item
        );
        $this->assertEquals(
            0, $item['Inventory Qty']
        );

        $this->assertArrayHasKey(
            'Standard Price', $item
        );
        $this->assertEquals(
            84.99, $item['Standard Price']
        );

        $this->assertArrayHasKey(
            'Best Price', $item
        );
        $this->assertEquals(
            84.99, $item['Best Price']
        );

        $this->assertArrayHasKey(
            'Retail Price', $item
        );
        $this->assertEquals(
            194.95, $item['Retail Price']
        );

        $this->assertArrayHasKey(
            'Retail Price', $item
        );
        $this->assertEquals(
            'Zero', $item['Drop Ship Availability']
        );

        $this->assertArrayHasKey(
            'Drop Ship Inventory Qty', $item
        );
        $this->assertEquals(
            0, $item['Drop Ship Inventory Qty']
        );

        $this->assertArrayHasKey(
            'Drop Ship Inventory Qty', $item
        );
        $this->assertEquals(
            '', $item['Next Expected PO Due Date']
        );
    }
}