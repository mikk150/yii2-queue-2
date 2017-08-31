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
    public function reserve()
    {
        $message = null;

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


        if (is_array($payload)) {
            $payload['reserved_at'] = time();
            $payload['attempt'] = (int)$payload['attempt'] + 1;
            $this->db->createCommand()->update($this->tableName, [
                'reserved_at' => $payload['reserved_at'], 'attempt' => $payload['attempt']],
                ['id' => $payload['id']]
            )->execute();

            if (is_resource($payload['job'])) {
                $payload['job'] = stream_get_contents($payload['job']);
            }

            $message = new Message([
                'id' => $payload['id'],
                'channel' => $payload['channel'],
                'message' => $payload['job'],
                'pushed_at' => $payload['pushed_at'],
                'ttr' => $payload['ttr'],
                'delay' => $payload['delay'],
                'priority' => $payload['priority'],
                'reserved_at' => $payload['reserved_at'],
                'attempt' => $payload['attempt'],
                'done_at' => $payload['done_at'],
            ]);
        }

        $this->mutex->release(__CLASS__ . $this->channel);

        return $message;
    }

    /**
     * @param array $payload
     */
    public function release($payload)
    {
        if ($this->deleteReleased) {
            $this->db->createCommand()->delete(
                $this->tableName,
                ['id' => $message->id]
            )->execute();
        } else {
            $this->db->createCommand()->update(
                $this->tableName,
                ['done_at' => time()],
                ['id' => $message->id]
            )->execute();
        }
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
