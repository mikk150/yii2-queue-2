<?php

namespace yii\queue\gearman;

use GearmanJob;
use GearmanWorker;
use yii\base\BaseObject;

class GearmanReserver extends BaseObject
{
    public $host = 'localhost';
    public $port = 4730;
    public $channel = 'queue';

    /**
     * Gearman worker to fetch jobs from
     * 
     * @var GearmanWorker
     */
    private $_worker;
    private $_jobs = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->_worker = new GearmanWorker();
        $this->_worker->addServer($this->host, $this->port);
        $this->_worker->setTimeout(100);

        $this->_worker->addFunction($this->channel, function (GearmanJob $payload) {
            list($ttr, $message) = explode(';', $payload->workload(), 2);
            $this->_jobs[] = [$payload->handle(), $message, $ttr, 1];
        });

        parent::init();
    }

    /**
     * Gets one job from gearman
     * 
     * @return array
     */
    public function reserve()
    {
        $this->_worker->work();
        return array_shift($this->_jobs);
    }
}
