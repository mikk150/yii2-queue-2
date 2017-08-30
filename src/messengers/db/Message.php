<?php

namespace yii\queue\messengers\db;

/**
* 
*/
class Message extends \yii\queue\Message
{
    public $id;
    public $channel;
    public $job;
    public $pushed_at;
    public $ttr;
    public $delay;
    public $priority;
    public $reserved_at;
    public $attempt;
    public $done_at;
}
