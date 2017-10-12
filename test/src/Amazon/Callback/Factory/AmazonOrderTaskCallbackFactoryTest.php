<?php

namespace rollun\test\amazonDropship\Amazon\Callback\Factory;

use rollun\amazonDropship\Amazon\Callback\AmazonOrderTaskCallback;
use rollun\amazonDropship\Amazon\Callback\Factory\AmazonOrderTaskCallbackFactory;

class AmazonOrderTaskCallbackFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $container;

    protected function setUp()
    {
        global $container;
        $this->container = $container;
    }

    public function test_create_shouldReturnObject()
    {
        $factory = new AmazonOrderTaskCallbackFactory();
        $instance = $factory($this->container, AmazonOrderTaskCallback::class);

        $this->assertInstanceOf(
            AmazonOrderTaskCallback::class, $instance
        );

        $result = $instance([]);
    }
}