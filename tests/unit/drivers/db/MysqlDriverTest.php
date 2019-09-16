<?php

namespace tests\unit\drivers\db;

class MysqlDriverTest extends TestCase
{
    public $queueConfig = [
        'mutex' => [
            'class' => \yii\mutex\MysqlMutex::class,
            'db' => 'mysql',
        ],
        'db' => 'mysql',
    ];
}
