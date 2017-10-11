<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 17:11
 */

namespace rollun\test\skeleton\Callback;

use rollun\callback\Callback\Interruptor\InterruptorAbstract;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Callback\Promiser;
use rollun\dic\InsideConstruct;
use rollun\test\skeleton\Callback\CallbackTestDataProvider;

class MultiplexerTest extends CallbackTestDataProvider
{


    /**
     * @param array $interruptors
     * @param $val
     * @dataProvider provider_multiplexerType()
     */
    public function test(array $interruptors, $val){

        $multiplexer = new Multiplexer($interruptors);
        $result = $multiplexer($val);
        $this->assertTrue(isset($result['data']));
    }
}
