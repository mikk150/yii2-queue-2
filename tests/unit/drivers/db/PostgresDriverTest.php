<?php

namespace tests\unit\drivers\db;

class PostgresDriverTest extends TestCase
{
    public $queueConfig = [
        'mutex' => [
            'class' => \yii\mutex\PgsqlMutex::class,
            'db' => 'pgsql',
        ],
        'db' => 'pgsql',
    ];
}
