<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\amqp;

use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
    private $_connection;
    /**
     * @var AMQPChannel
     */
    private $_channel;

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

        $id = uniqid('', true);
        $this->getChannel()->basic_publish(
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
        // $this->open();
        if (!$this->_reserver) {
            $this->_reserver = new AmQpReserver($this->getChannel(), [
                'queueName' => $this->queueName
            ]);
        }
        return $this->_reserver;
    }

    /**
     * Gets AmQP channel
     *
     * @return AbstractChannel
     */
    public function getChannel()
    {
        if (!$this->_channel) {
            $this->_channel = $this->getConnection()->channel();
            $this->_channel->queue_declare($this->queueName, false, true, false, false);
            $this->_channel->exchange_declare($this->exchangeName, 'direct', false, true, false);
            $this->_channel->queue_bind($this->queueName, $this->exchangeName);
        }

        return $this->_channel;
    }

    /**
     * Gets AmpQP Connection
     *
     * @return AbstractConnection
     */
    public function getConnection()
    {
        if (!$this->_connection) {
            $this->_connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
        }
        return $this->_connection;
    }

    protected function closeChannel()
    {
        if (!$this->_channel) {
            return;
        }
        $this->_channel->close();
        $this->_channel = null;
    }

    /**
     * Closes connection and channel.
     */
    public function close()
    {
        $this->closeChannel();
        if ($this->_connection) {
            $this->_connection->close();
            $this->_connection = null;
        }
    }
}
