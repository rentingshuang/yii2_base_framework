<?php

namespace console\commands;

use yii\console\Controller;
use common\components\CommonFun;
use common\models\CommonWorker;


class YiiBaseQueueController extends Controller
{
    public function actionIndex($queueName = 'yiiframebase', $attempt = 3, $memeory = 128, $sleep = 2, $delay = 2) {
    	CommonWorker::listen (\Yii::$app->queue, $queueName, $attempt, $memeory, $sleep, $delay );
    }
}
