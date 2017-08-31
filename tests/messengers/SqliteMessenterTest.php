<?php

namespace tests\messengers;

use yii\queue\messengers\db\Messenger;

/**
* 
*/
class SqliteMessengerTest extends TestCase
{
    public function createMessenger()
    {
        return new Messenger([
            'db' => 'sqlite',
            'mutex' => [
                'class' => \yii\mutex\FileMutex::class,
            ]
        ]);
    }
}
