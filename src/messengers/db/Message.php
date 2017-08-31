<?php

namespace yii\queue\messengers\db;

/**
* 
*/
class Message extends \yii\queue\Message
{
    public $channel;
    
    public $pushed_at;
    
    public $delay;
    
    public $priority;
    
    public $reserved_at;
    
    public $done_at;
}
