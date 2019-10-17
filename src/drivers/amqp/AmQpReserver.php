<?php

namespace yii\queue\amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\BaseObject;

class AmQpReserver extends BaseObject
{
    public $queueName;

    /**
     * @var AMQPChannel
     */
    private $_channel;

    private $_jobs = [];

    /**
     * @param AMQPChannel $channel
     * @param array       $config
     * 
     * @inheritdoc
     */
    public function __construct(AMQPChannel $channel, $config = [])
    {
        $this->_channel = $channel;

        parent::__construct($config);
    }

    /**
     * 
     * @inheritdoc
     */
    public function init()
    {
        $callback = function (AMQPMessage $payload) {
            $id = $payload->get('message_id');
            list($ttr, $message) = explode(';', $payload->body, 2);
            $this->_jobs[] = [$id, $message, $ttr, 1, $payload];
        };
        $this->_channel->basic_qos(0, 0, null);
        $this->_channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        parent::init();
    }

    /**
     * Gets one job from gearman
     * 
     * @return array
     */
    public function reserve()
    {
        try {
            $this->_channel->wait(null, true, 0.5);
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
        }
        return array_shift($this->_jobs);
    }
}
