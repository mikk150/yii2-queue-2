<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\beanstalk;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use yii\base\InvalidArgumentException;
use yii\queue\cli\AsyncQueue;

/**
 * Beanstalk Queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends AsyncQueue
{
    /**
     * @var string connection host
     */
    public $host = 'localhost';
    /**
     * @var int connection port
     */
    public $port = PheanstalkInterface::DEFAULT_PORT;
    /**
     * @var string beanstalk tube
     */
    public $tube = 'queue';
    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @inheritdoc
     */
    protected function reserve()
    {
        if ($payload = $this->getPheanstalk()->reserveFromTube($this->tube, 0)) {
            $info = $this->getPheanstalk()->statsJob($payload);
            return [
                $payload->getId(),
                $payload->getData(),
                $info->ttr,
                $info->reserves,
                $payload,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($payload)
    {
        list(,,,,$job) = $payload;
        $this->getPheanstalk()->delete($job);
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("Unknown message ID: $id.");
        }

        try {
            $stats = $this->getPheanstalk()->statsJob($id);
            if ($stats['state'] === 'reserved') {
                return self::STATUS_RESERVED;
            }

            return self::STATUS_WAITING;
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Server reported NOT_FOUND') {
                return self::STATUS_DONE;
            }

            throw $e;
        }
    }

    /**
     * Removes a job by ID.
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove($id)
    {
        try {
            $this->getPheanstalk()->delete(new Job($id, null));
            return true;
        } catch (ServerException $e) {
            if (strpos($e->getMessage(), 'NOT_FOUND') !== false) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        return $this->getPheanstalk()->putInTube(
            $this->tube,
            $message,
            $priority ?: PheanstalkInterface::DEFAULT_PRIORITY,
            $delay,
            $ttr
        );
    }

    /**
     * @return object tube statistics
     */
    public function getStatsTube()
    {
        return $this->getPheanstalk()->statsTube($this->tube);
    }

    /**
     * @return Pheanstalk
     */
    protected function getPheanstalk()
    {
        if (!$this->_pheanstalk) {
            $this->_pheanstalk = new Pheanstalk($this->host, $this->port);
        }
        return $this->_pheanstalk;
    }

    private $_pheanstalk;
}
