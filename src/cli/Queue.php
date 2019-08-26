<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\helpers\Inflector;
use yii\queue\cli\LoadWatcher;
use yii\queue\cli\SignalLoop;
use yii\queue\cli\WorkerEvent;
use yii\queue\Queue as BaseQueue;

/**
 * Queue with CLI.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends BaseQueue implements BootstrapInterface
{
    /**
     * @event WorkerEvent that is triggered when the worker is started.
     * @since 2.0.2
     */
    const EVENT_WORKER_START = 'workerStart';
    /**
     * @event WorkerEvent that is triggered each iteration between requests to queue.
     * @since 2.0.3
     */
    const EVENT_WORKER_LOOP = 'workerLoop';
    /**
     * @event WorkerEvent that is triggered when the worker is stopped.
     * @since 2.0.2
     */
    const EVENT_WORKER_STOP = 'workerStop';

    /**
     * @var array|string
     * @since 2.0.2
     */
    public $loopConfig = SignalLoop::class;
    /**
     * @var string command class name
     */
    public $commandClass = Command::class;
    /**
     * @var array of additional options of command
     */
    public $commandOptions = [];
    /**
     * @var callable|null
     * @internal for worker command only
     */
    public $messageHandler;
    /**
     * @var integer Maximum CPU usage after which no new jobs will be proccessed
     */
    public $maxCpuLoad = 80;

    /**
     * @var int|null current process ID of a worker.
     * @since 2.0.2
     */
    private $_workerPid;

    /**
     * @var LoopInterface
     */
    private $_loop;

    /**
     * Gets react EventLoop, if not present, creates one
     *
     * @return LoopInterface
     */
    public function getLoop()
    {
        if (!$this->_loop) {
            $this->_loop = Factory::create();
        }
        return $this->_loop;
    }

    protected function doWork(callable $canContinue, $repeat, $timeout)
    {
        if ($canContinue()) {
            if ($this->getLoadWatcher()->getPercentage() > $this->maxCpuLoad) {
                $this->getLoop()->addTimer(
                    0.1,
                    function () use ($canContinue, $repeat, $timeout) {
                        $this->doWork($canContinue, $repeat, $timeout);
                    }
                );
                return;
            }
            if (($payload = $this->reserve()) !== null) {
                list($id, $message, $ttr, $attempt) = $payload;
                $this->handleMessage($id, $message, $ttr, $attempt)->then(
                    function () use ($payload) {
                        $this->delete($payload);
                    },
                    function ($event) {
                        $this->handleError($event);
                    }
                );
                $this->getLoop()->futureTick(
                    function () use ($canContinue, $repeat, $timeout) {
                        $this->doWork($canContinue, $repeat, $timeout);
                    }
                );
                return;
            }
            if ($repeat) {
                $this->getLoop()->addTimer(
                    $timeout,
                    function () use ($canContinue, $repeat, $timeout) {
                        $this->doWork($canContinue, $repeat, $timeout);
                    }
                );
            }
        }
    }

    /**
     * @var LoadWatcher Load measurement helper
     */
    private $_loadWatcher;

    /**
     * gets Load measurement helper
     * 
     * @return LoadWatcher
     */
    protected function getLoadWatcher()
    {
        if (!$this->_loadWatcher) {
            $this->_loadWatcher = new LoadWatcher($this->getLoop());
        }

        return $this->_loadWatcher;
    }

    /**
     * @return string command id
     * @throws
     */
    protected function getCommandId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('Queue must be an application component.');
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                'class' => $this->commandClass,
                'queue' => $this,
            ] + $this->commandOptions;
        }
    }

    /**
     * Runs worker.
     *
     * @param callable $handler
     * @return null|int exit code
     * @since 2.0.2
     */
    protected function runWorker(callable $handler)
    {
        $this->_workerPid = getmypid();
        /** @var LoopInterface $loop */
        $loop = Yii::createObject($this->loopConfig, [$this]);

        $event = new WorkerEvent(['loop' => $loop]);
        $this->trigger(self::EVENT_WORKER_START, $event);
        if ($event->exitCode !== null) {
            return $event->exitCode;
        }

        try {
            call_user_func($handler, function () use ($loop, $event) {
                $this->trigger(self::EVENT_WORKER_LOOP, $event);
                return $event->exitCode === null && $loop->canContinue();
            });
        } finally {
            $this->trigger(self::EVENT_WORKER_STOP, $event);
            $this->_workerPid = null;
        }

        return $event->exitCode;
    }

    /**
     * Gets process ID of a worker.
     *
     * @inheritdoc
     * @return int|null
     * @since 2.0.2
     */
    public function getWorkerPid()
    {
        return $this->_workerPid;
    }

    /**
     * @inheritdoc
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message, $ttr, $attempt);
        }

        return parent::handleMessage($id, $message, $ttr, $attempt);
    }

    /**
     * @param string $id of a message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @param int|null $workerPid of worker process
     * @return Promise
     * @internal for worker command only
     */
    public function execute($id, $message, $ttr, $attempt, $workerPid)
    {
        $this->_workerPid = $workerPid;
        return parent::handleMessage($id, $message, $ttr, $attempt);
    }
}
