<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.03.17
 * Time: 15:26
 */

namespace rollun\test\skeleton\Callback;

use rollun\callback\Callback\Ticker;
use rollun\installer\Command;

class TickerTest extends CallbackTestDataProvider
{
    const FILE_WITH_RESULT = 'ticker_results';
    /**
     * @param $tickerCallback
     * @param $ticksCount
     * @param $tickDuration
     * @param $delayMicroSecond
     * @param $value
     * @internal param array $interruptors
     * @internal param $val
     * @dataProvider providerTickerType()
     */
    public function test($tickerCallback, $ticksCount, $tickDuration, $delayMicroSecond)
    {
        $file = Command::getDataDir() . static::FILE_WITH_RESULT;
        if(file_exists($file)) {
           unlink($file);
        }

        $sleepTime = ($ticksCount * $tickDuration) + ($delayMicroSecond / 1000000);
        $ticker = new Ticker($tickerCallback, $ticksCount, $tickDuration, $delayMicroSecond);

        $startTime = microtime(true);
        $ticker($file);
        $finishTime = microtime(true);
        
        $workTimeDiff = abs(($finishTime - $startTime) - $sleepTime);
        $this->assertTrue($workTimeDiff >= 0 && $workTimeDiff <= 0.09);

        $data = array_diff(explode("\n", file_get_contents($file)), [""]);
        $this->assertTrue(count($data) == $ticksCount);

        for ($i = 1; $i < count($data); $i++) {
            $prevTime = $data[$i - 1];
            $currTime = $data[$i];
            $diff = ((float)$currTime - (float)$prevTime) - $tickDuration;
            $this->assertTrue($diff >= 0 && $diff <= 0.09);
        }
    }
}
