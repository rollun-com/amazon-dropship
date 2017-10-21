<?php

namespace rollun\amazonDropship\Amazon\Callback;

use rollun\amazonDropship\Amazon\Callback\Factory\AmazonOrderTaskCallbackFactory;
use rollun\callback\Callback\Callback;
use rollun\logger\Logger;

class AmazonOrderTaskCallback extends Callback
{
    protected $value;
    protected $schedule;

    /**
     * TaskCallback constructor.
     *
     * @param callable $callback
     * @param $value
     */
    public function __construct($callback, $value)
    {
        parent::__construct($callback);
        $this->schedule = $value[AmazonOrderTaskCallbackFactory::SCHEDULE_KEY];
        unset($value[AmazonOrderTaskCallbackFactory::SCHEDULE_KEY]);
        $this->value = $value;
    }

    public function __invoke($value)
    {
        $logger = new Logger();
        $logger->debug('Try to call Amazon Order task on schedule');

        // Runs task only on schedule
        if ($this->checkSchedule()) {
            $logger->debug('The schedule matches with the current time so the task will be run.');
            try {
                $result = parent::__invoke($this->value);
            } catch (\Exception $e) {
                $result = null;
                $logger->critical("During an executing an error occurred: " . $e->getMessage());
            } finally {
                return $result;
            }
        }
        $logger->debug("The schedule doesn't match with the current time so do nothing.");
        return false;
    }

    public function __sleep()
    {
        $classPropertiesToSerialize = parent::__sleep();
        return array_merge($classPropertiesToSerialize, ['value', 'schedule']);
    }

    protected  function checkSchedule()
    {
        $runCondition = false;

        if (!isset($this->schedule['minutes'])) {
            $this->schedule['minutes'][] = '*';
        }
        if (!isset($this->schedule['hours'])) {
            $this->schedule['hours'][] = '*';
        }

        if (in_array('*', $this->schedule['minutes'], true)) {
            $runCondition = true;
        } else {
            $minute = (int) date('i');
            if (in_array($minute, $this->schedule['minutes'], true)) {
                $runCondition = true;
            }
        }
        if (in_array('*', $this->schedule['hours'], true)) {
            $runCondition &= true;
        } else {
            $hour = (int) date('H');
            if (in_array($hour, $this->schedule['hours'], true)) {
                $runCondition &= true;
            }
        }
        $logger = new Logger();
        $logger->debug("The set schedule is: hours [" . join($this->schedule['hours'], ', ') . "]; minutes [" . join($this->schedule['minutes'], ', ') . "]");
        return (boolean) $runCondition;
    }
}