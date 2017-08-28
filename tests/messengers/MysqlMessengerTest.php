<?php

namespace tests\messengers;

use yii\queue\messengers\db\Messenger;

/**
* 
*/
class MysqlMessengerTest extends TestCase
{
    public function createMessenger()
    {
        return new Messenger([
            'db' => 'mysql',
            'mutex' => [
                'class' => \yii\mutex\MysqlMutex::class,
                'db' => 'mysql',
            ]
        ]);
    }
}
