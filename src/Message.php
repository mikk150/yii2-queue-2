<?php

namespace yii\queue;

use \yii\base\Object;

/**
 * 
 */
class Message extends Object
{
    public $id;
    
    public $message;
    
    public $ttr;
    
    public $attempt;
}
