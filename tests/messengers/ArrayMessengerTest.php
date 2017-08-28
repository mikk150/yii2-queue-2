<?php

namespace tests\messengers;

use yii\queue\messengers\arraymessenger\ArrayMessenger;

/**
* 
*/
class ArrayMessengerTest extends TestCase
{
    public function createMessenger()
    {
        return new ArrayMessenger();
    }
}
