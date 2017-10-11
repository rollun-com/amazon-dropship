<?php

namespace rollun\amazonDropship\Amazon\Callback;

use rollun\callback\Callback\Callback;

class TaskCallback extends Callback
{
    protected $value;

    /**
     * TaskCallback constructor.
     * @param $task
     * @param $value
     */
    public function __construct($callback, $value)
    {
        parent::__construct($callback);
        $this->value = $value;
    }

    public function __invoke($value)
    {
        return $this->callback->__invoke($this->value);
    }
}