<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app\benchmark;

use tests\app\DummyJob;
use Yii;

/**
 * Benchmark commands.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Controller extends \yii\console\Controller
{
    private $startedAt;

    public function actions()
    {
        return [
            'waiting' => waiting\Action::class,
            'queue' => queue\Action::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $this->startedAt = time();
        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        $duration = time() - $this->startedAt;
        $this->stdout("\nCompleted in {$duration} s.\n");
        return parent::afterAction($action, $result);
    }

    public function actionPushLoadsOfJobs($queue, $jobs = 1000)
    {
        for ($i=0; $i < $jobs; $i++) { 
            $job = new DummyJob();
            Yii::$app->get($queue)->push($job);
        }
    }
}
