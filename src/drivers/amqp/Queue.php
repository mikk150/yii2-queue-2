<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\queue\cli\AsyncQueue;

/**
 * Amqp Queue.
 *
 * @deprecated since 2.0.2 and will be removed in 3.0. Consider using amqp_interop driver instead.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends AsyncQueue
{
    public $host = 'localhost';
    public $port = 5672;
    public $user = 'guest';
    public $password = 'guest';
    public $queueName = 'queue';
    public $exchangeName = 'exchange';
    public $vhost = '/';
    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;
    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var AmQpReserver
     */
    private $_reserver;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }

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
     * Deletes reserved message.
     *
     * @param array $payload
     */
    protected function delete($payload)
    {
        $payload[4]->delivery_info['channel']->basic_ack($payload[4]->delivery_info['delivery_tag']);
    }

    /**
     * Clears the queue.
     */
    public function clear()
    {
        while ($payload = $this->reserve()) {
            Yii::info('cleaning ' . $payload[0], __CLASS__);
            $this->delete($payload);
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $this->open();
        $id = uniqid('', true);
        $this->channel->basic_publish(
            new AMQPMessage("$ttr;$message", [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $id,
            ]),
            $this->exchangeName
        );
        $this->closeChannel();
        return $id;
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @return AmQpReserver
     */
    protected function getReserver()
    {
        $this->open();
        if (!$this->_reserver) {
            $this->_reserver = new AmQpReserver($this->channel, [
                'queueName' => $this->queueName
            ]);
        }
        return $this->_reserver;
    }

    /**
     * Opens connection and channel.
     */
    protected function open()
    {
        if (!$this->connection) {
            $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
        }
        if ($this->channel) {
            return;
        }
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
    }

    protected function closeChannel()
    {
        if (!$this->channel) {
            return;
        }
        $this->channel->close();
    }

    /**
     * Closes connection and channel.
     */
    public function close()
    {
        $this->closeChannel();
        $this->connection->close();
    }
}
