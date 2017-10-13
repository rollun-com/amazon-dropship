<?php

namespace rollun\amazonDropship\Amazon\Callback;

use rollun\callback\Callback\Callback;
use rollun\installer\Command;

class AmazonOrderTaskCallback extends Callback
{
    protected $value;

    /**
     * TaskCallback constructor.
     *
     * @param $callback
     * @param $value
     */
    public function __construct($callback, $value)
    {
        parent::__construct($callback);
        $this->value = $value;
    }

    public function __invoke($value)
    {
        $filename = Command::getDataDir() . 'logs/amazon_order_task_callback.log';
        if (!file_exists($filename)) {
            file_put_contents($filename, 0);
        }
        $executionsCount = intval(file_get_contents($filename));
        if (1 == 1 || $executionsCount % 60 == 0) {
            file_put_contents($filename, 1);
            return parent::__invoke($this->value);
        }
        file_put_contents($filename, ++$executionsCount);
        return false;
    }

    public function __sleep()
    {
        $classPropertiesToSerialize = parent::__sleep();
        return array_merge($classPropertiesToSerialize, ['value']);
    }
}