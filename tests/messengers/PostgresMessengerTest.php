<?php

namespace tests\messengers;

use yii\queue\messengers\db\Messenger;

/**
* 
*/
class PostgresMessengerTest extends TestCase
{
    public function createMessenger()
    {
        return new Messenger([
            'db' => 'pgsql',
            'mutex' => [
                'class' => \yii\mutex\PgsqlMutex::class,
                'db' => 'pgsql',
            ],
            'mutexTimeout' => 0
        ]);
    }
}
