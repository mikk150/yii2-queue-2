<?php

namespace yii\queue\messengers\db;

use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;

/**
* 
*/
class Messenger extends \yii\queue\messengers\Messenger
{
    /**
     * @var Connection|array|string
     */
    public $db = 'db';

    /**
     * @var Mutex|array|string
     */
    public $mutex = 'mutex';

    /**
     * @var int timeout
     */
    public $mutexTimeout = 3;

    /**
     * @var string table name
     */
    public $tableName = '{{%queue}}';

    /**
     * @var string
     */
    public $channel = 'queue';

    /**
     * @var boolean ability to delete released messages from table
     */
    public $deleteReleased = true;

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    /**
     * @inheritdoc
     */
    public function pop()
    {
        if (!$this->mutex->acquire(__CLASS__ . $this->channel, $this->mutexTimeout)) {
            throw new Exception("Has not waited the lock.");
        }

        $payload = (new Query())
           ->from($this->tableName)
           ->andWhere(['channel' => $this->channel, 'reserved_at' => null])
           ->andWhere('[[pushed_at]] <= :time - delay', [':time' => time()])
           ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
           ->limit(1)
           ->one($this->db);

        $this->mutex->release(__CLASS__ . $this->channel);

        if ($payload) {
            return $payload['message'];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function push($message, $delay = 0, $priority = 1024)
    {
        $this->db->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'job' => $message,
            'pushed_at' => time(),
            'ttr' => 0,
            'delay' => $delay,
            'priority' => $priority ?: 1024,
        ])->execute();
        $tableSchema = $this->db->getTableSchema($this->tableName);
        $id = $this->db->getLastInsertID($tableSchema->sequenceName);
        return $id;
    }
}
