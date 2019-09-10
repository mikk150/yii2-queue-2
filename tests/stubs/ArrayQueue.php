<?php

namespace tests\stubs;

use yii\queue\cli\AsyncQueue;

class ArrayQueue extends AsyncQueue
{
    /**
     * @var bool
     */
    public $handle = false;

    /**
     * @var array of payloads
     */
    public $payloads = [];

    public $loadWatcher = [
        'class' => DummyLoadWatcher::class
    ];
    
    /**
     * @var int last pushed ID
     */
    private $pushedId = 0;
    /**
     * @var int started ID
     */
    private $startedId = 0;
    /**
     * @var int last finished ID
     */
    private $finishedId = 0;

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to sleep before next iteration.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run($repeat, $timeout = 0)
    {
        return $this->runWorker(
            function (callable $canContinue) use ($repeat, $timeout) {
                $this->doWork($canContinue, $repeat, $timeout);
                $this->getLoop()->run();
            }
        );
    }

    /**
     * Reserves message for execute.
     *
     * @return array|null payload
     */
    protected function reserve()
    {
        return array_shift($this->payloads);
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        array_push($this->payloads, [$ttr, $message, 0, 0]);
        return ++$this->pushedId;
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        if (!is_int($id) || $id <= 0 || $id > $this->pushedId) {
            throw new InvalidArgumentException("Unknown messages ID: $id.");
        }

        if ($id <= $this->finishedId) {
            return self::STATUS_DONE;
        }

        if ($id === $this->startedId) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_WAITING;
    }
}
