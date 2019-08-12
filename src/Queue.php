<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

use React\EventLoop\Factory;
use React\Promise\Promise;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\VarDumper;
use yii\queue\ExecEvent;
use yii\queue\InvalidJobException;
use yii\queue\PushEvent;
use yii\queue\serializers\PhpSerializer;
use yii\queue\serializers\SerializerInterface;

/**
 * Base Queue.
 *
 * @property null|int $workerPid
 * @since 2.0.2
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends Component
{
    /**
     * @event PushEvent
     */
    const EVENT_BEFORE_PUSH = 'beforePush';
    /**
     * @event PushEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';
    /**
     * @event ExecEvent
     */
    const EVENT_BEFORE_EXEC = 'beforeExec';
    /**
     * @event ExecEvent
     */
    const EVENT_AFTER_EXEC = 'afterExec';
    /**
     * @event ExecEvent
     */
    const EVENT_AFTER_ERROR = 'afterError';
    /**
     * @see Queue::isWaiting()
     */
    const STATUS_WAITING = 1;
    /**
     * @see Queue::isReserved()
     */
    const STATUS_RESERVED = 2;
    /**
     * @see Queue::isDone()
     */
    const STATUS_DONE = 3;

    /**
     * @var bool whether to enable strict job type control.
     * Note that in order to enable type control, a pushing job must be [[JobInterface]] instance.
     * @since 2.0.1
     */
    public $strictJobType = true;
    /**
     * @var SerializerInterface|array
     */
    public $serializer = PhpSerializer::class;
    /**
     * @var int default time to reserve a job
     */
    public $ttr = 300;
    /**
     * @var int default attempt count
     */
    public $attempts = 1;

    private $pushTtr;
    private $pushDelay;
    private $pushPriority;

    /**
     * @var ReactLoopInterface
     */
    private $_loop;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->serializer = Instance::ensure($this->serializer, SerializerInterface::class);

        if (!is_numeric($this->ttr)) {
            throw new InvalidConfigException('Default TTR must be integer.');
        }
        $this->ttr = (int) $this->ttr;
        if ($this->ttr <= 0) {
            throw new InvalidConfigException('Default TTR must be greater that zero.');
        }

        if (!is_numeric($this->attempts)) {
            throw new InvalidConfigException('Default attempts count must be integer.');
        }
        $this->attempts = (int) $this->attempts;
        if ($this->attempts <= 0) {
            throw new InvalidConfigException('Default attempts count must be greater that zero.');
        }
    }

    /**
     * Sets TTR for job execute.
     *
     * @param int|mixed $value
     * @return $this
     */
    public function ttr($value)
    {
        $this->pushTtr = $value;
        return $this;
    }

    /**
     * Sets delay for later execute.
     *
     * @param int|mixed $value
     * @return $this
     */
    public function delay($value)
    {
        $this->pushDelay = $value;
        return $this;
    }

    /**
     * Sets job priority.
     *
     * @param mixed $value
     * @return $this
     */
    public function priority($value)
    {
        $this->pushPriority = $value;
        return $this;
    }

    /**
     * Pushes job into queue.
     *
     * @param JobInterface|mixed $job
     * @return string|null id of a job message
     */
    public function push($job)
    {
        $event = new PushEvent([
            'job' => $job,
            'ttr' => $this->pushTtr ?: (
                $job instanceof RetryableJobInterface
                    ? $job->getTtr()
                    : $this->ttr
            ),
            'delay' => $this->pushDelay ?: 0,
            'priority' => $this->pushPriority,
        ]);
        $this->pushTtr = null;
        $this->pushDelay = null;
        $this->pushPriority = null;

        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        if ($event->handled) {
            return null;
        }

        if ($this->strictJobType && !($event->job instanceof JobInterface)) {
            throw new InvalidArgumentException('Job must be instance of JobInterface.');
        }

        if (!is_numeric($event->ttr)) {
            throw new InvalidArgumentException('Job TTR must be integer.');
        }
        $event->ttr = (int) $event->ttr;
        if ($event->ttr <= 0) {
            throw new InvalidArgumentException('Job TTR must be greater that zero.');
        }

        if (!is_numeric($event->delay)) {
            throw new InvalidArgumentException('Job delay must be integer.');
        }
        $event->delay = (int) $event->delay;
        if ($event->delay < 0) {
            throw new InvalidArgumentException('Job delay must be positive.');
        }

        $message = $this->serializer->serialize($event->job);
        $event->id = $this->pushMessage($message, $event->ttr, $event->delay, $event->priority);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param string $message
     * @param int $ttr time to reserve in seconds
     * @param int $delay
     * @param mixed $priority
     * @return string id of a job message
     */
    abstract protected function pushMessage($message, $ttr, $delay, $priority);

    /**
     * Uses for CLI drivers and gets process ID of a worker.
     *
     * @since 2.0.2
     */
    public function getWorkerPid()
    {
        return null;
    }

    /**
     * @param string $id of a job message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return Promise
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        list($job, $error) = $this->unserializeMessage($message);
        $event = new ExecEvent([
            'id' => $id,
            'job' => $job,
            'ttr' => $ttr,
            'attempt' => $attempt,
            'error' => $error,
        ]);

        return new Promise(function ($fulfill, $reject) use ($event) {
            $this->trigger(self::EVENT_BEFORE_EXEC, $event);
            if ($event->handled) {
                call_user_func($fulfill, $event);
                return ;
            }
            if ($event->error) {
                call_user_func($reject, $event);
                return ;
            }
            try {
                $event->result = $event->job->execute($this);
            } catch (\Exception $error) {
                $event->error = $error;
                call_user_func($reject, $event);
                return ;
            } catch (\Throwable $error) {
                $event->error = $error;
                call_user_func($reject, $event);
                return ;
            }
            $this->trigger(self::EVENT_AFTER_EXEC, $event);
            call_user_func($fulfill, $event);
        });
    }

    /**
     * Unserializes.
     *
     * @param string $id of the job
     * @param string $serialized message
     * @return array pair of a job and error that
     */
    public function unserializeMessage($serialized)
    {
        try {
            $job = $this->serializer->unserialize($serialized);
        } catch (\Exception $e) {
            return [null, new InvalidJobException($serialized, $e->getMessage(), 0, $e)];
        }

        if ($job instanceof JobInterface) {
            return [$job, null];
        }

        return [null, new InvalidJobException($serialized, sprintf(
            'Job must be a JobInterface instance instead of %s.',
            VarDumper::dumpAsString($job)
        ))];
    }

    /**
     * @param ExecEvent $event
     * @return bool
     * @internal
     */
    public function handleError(ExecEvent $event)
    {
        $event->retry = $event->attempt < $this->attempts;
        if ($event->error instanceof InvalidJobException) {
            $event->retry = false;
        } elseif ($event->job instanceof RetryableJobInterface) {
            $event->retry = $event->job->canRetry($event->attempt, $event->error);
        }
        $this->trigger(self::EVENT_AFTER_ERROR, $event);
        return !$event->retry;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isWaiting($id)
    {
        return $this->status($id) === self::STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isReserved($id)
    {
        return $this->status($id) === self::STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isDone($id)
    {
        return $this->status($id) === self::STATUS_DONE;
    }

    /**
     * Gets react EventLoop, if not present, creates one
     *
     * @return ReactLoopInterface
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
                return ;
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
     * @param string $id of a job message
     * @return int status code
     */
    abstract public function status($id);
}
