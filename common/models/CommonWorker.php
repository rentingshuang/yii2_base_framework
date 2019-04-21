<?php

namespace common\models;

use Yii;
use yii\base\Model;
use shmilyzxt\queue\base\Job;
use shmilyzxt\queue\base\Queue;
use common\components\CommonFun;


class CommonWorker extends Model {

	/**
	 * 启用一个队列后台监听任务
	 * @param Queue $queue
	 * @param string $queueName 监听队列的名称(在pushon的时候把任务推送到哪个队列，则需要监听相应的队列才能获取任务)
	 * @param int $attempt 队列任务失败尝试次数，0为不限制
	 * @param int $memory 允许使用的最大内存
	 * @param int $sleep 每次检测的时间间隔 
	 */
	public static function listen(Queue $queue, $queueName = 'yiiframebase', $attempt = 3, $memory = 512, $sleep = 3, $delay = 2){
		while (true){
		//for($i = 0;$i < 50; $i++){
			try{				
				$job = $queue->pop($queueName);
			}catch (\Exception $e){
				continue;
			}
			if($job instanceof Job){
				//echo $queue->getJobCount($queueName)."\r\n";
				//echo $job->getAttempts()."\r\n";
				$payload = $job->getPayload();
				$payload = json_encode(unserialize($payload),true);
				
				if($attempt > 0 && $job->getAttempts() > $attempt){
					//echo "listen-->","failed\r\n";
					try {
						$job->failed();
					}catch (\Exception $e){
						//$job->delete();
						self::logErr($e,$queueName, $payload );
					}
				}else{
					try{
						$job->execute();
					}catch (\Exception $e){
						self::logErr($e,$queueName,$payload);
						if (! $job->isDeleted()) {
							$job->release($delay);
						}
					}
				}
			}else{
				self::sleep($sleep);
			}
			if (self::memoryExceeded($memory)) {
				self::stop();
			}
		}
	}
	
	public static function logErr($e,$queueName = '',$payload = ''){
		CommonFun::log("任务执行异常,对列名称：{$queueName}，原因：".$e->getFile().'->'.$e->getLine().'->'.$e->getMessage()."，任务内容：{$payload}",'baseListenException','baseListen');
	}
	
	/**
	 * 判断内存使用是否超出
	 * @param  int   $memoryLimit
	 * @return bool
	 */
	public static function memoryExceeded($memoryLimit)
	{
		return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
	}
	
	/**
	 * 停止队列监听
	 */
	public static function stop(){
		die;
	}
	
	/**
	 * 休眠
	 */
	public static function sleep($seconds){
		sleep($seconds);
		echo date('Y-m-d H:i:s')," sleep\r\n";
	}
	
}
