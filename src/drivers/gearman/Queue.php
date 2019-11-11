<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\gearman;

use GearmanClient;
use yii\base\NotSupportedException;
use yii\queue\cli\AsyncQueue;

/**
 * Gearman Queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends AsyncQueue
{
    public $host = 'localhost';
    public $port = 4730;
    public $channel = 'queue';

    CONST PRIORITY_HIGH_LIMIT = 683;
    CONST PRIORITY_LOW_LIMIT = 1365;

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @var GearmanReserver
     */
    private $_reserver;

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
        return $this->getReserver()->reserve();
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        if ($priority && is_numeric($priority)) {
            if ($priority <= self::PRIORITY_HIGH_LIMIT && $priority <= self::PRIORITY_LOW_LIMIT) {
                $priority = 'high';
            }
            if ($priority >= self::PRIORITY_LOW_LIMIT) {
                $priority = 'low';
            }
        }

        switch ($priority) {
            case 'high':
                return $this->getClient()->doHighBackground($this->channel, "$ttr;$message");
            case 'low':
                return $this->getClient()->doLowBackground($this->channel, "$ttr;$message");
            default:
                return $this->getClient()->doBackground($this->channel, "$ttr;$message");
        }
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        $status = $this->getClient()->jobStatus($id);
        if ($status[0] && !$status[1]) {
            return self::STATUS_WAITING;
        }

        if ($status[0] && $status[1]) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_DONE;
    }

    /**
     * Gets gearman reserver
     *
     * @return GearmanReserver
     */
    protected function getReserver()
    {
        if (!$this->_reserver) {
            $this->_reserver = new GearmanReserver([
                'host' => $this->host,
                'port' => $this->port,
                'channel' => $this->channel,
            ]);
        }
        return $this->_reserver;
    }

    /**
     * @return \GearmanClient
     */
    protected function getClient()
    {
        if (!$this->_client) {
            $this->_client = new GearmanClient;
            $this->_client->addServer($this->host, $this->port);
        }
        return $this->_client;
    }

    private $_client;
}
