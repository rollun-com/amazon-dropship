<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 16.01.17
 * Time: 14:47
 */

namespace rollun\test\skeleton\Api;

use PHPUnit_Framework_TestCase;

class HelloActionTestProvider extends PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function providerDevQuery()
    {
        return [
            [
                "dev", "[dev] Hello !", 'text/html'
            ],
            [
                "dev", "[dev] Hello !", 'application/json'
            ],
        ];
    }

    public function providerProdQuery()
    {
        return [
            [
                "prod", "[prod] Hello !", 'text/html'
            ],
            [
                "prod", "[prod] Hello !", 'application/json'
            ],
        ];
    }
}
