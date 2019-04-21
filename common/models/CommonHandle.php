<?php

namespace common\models;

use Yii;
use shmilyzxt\queue\base\JobHandler;
use common\components\CommonFun;
use common\components\CommonEnum;

/**
 * 顶层公用对列处理类
 * 
 * @author RTS 2017年2月5日 23:41:17
 *        
 */
class CommonHandle extends JobHandler {
	
	/**
	 * 回调处理
	 * @author RTS 2017年2月6日 13:01:13
	 * 
	 */
	public function handle($job, $data) {
		$getAttempts = $job->getAttempts ();
		if ($getAttempts > 3) {
			$this->failed ( $job );
		}
		$qdata = $data['data'];
		$data = $data['queueInfo'];
		
		$topic = CommonFun::getArrayValue ( $data, 'msgTopic', - 1 );
		$msgTopicId = CommonFun::getArrayValue ( $data, 'msgTopicId', 0 );
		
		if(!empty($msgTopicId)){
			$cacheName = 'handle-msgTopicId-'.$msgTopicId;
			$cacheValue = CommonFun::getCache ( $cacheName );
			if (! empty($cacheValue)){
				return true;
			}
			CommonFun::setCache($cacheName,1,1);
		}
		
		$res = false;
		$msgTopic = CommonEnum::$msgTopic;
		switch ($topic) {
			case $msgTopic['email'] :
				$res = CommonEmail::send ( $qdata );
				break;
			case $msgTopic['sms'] :
				$res = CommonFun::sendSms( $qdata );
				break;
			case $msgTopic['addOrder2OpenApi'] :
				$res = CommonOpenApi::addOrder( $qdata );
				break;
			case $msgTopic['cancelOrder2OpenApi'] :
				$res = CommonOpenApi::cancelOrder( $qdata );
				break;
			case $msgTopic['addOrder2Business'] :
				$res = CommonBusiness::addOrder( $qdata );
				break;
			case $msgTopic['receiveOrder'] ://快递员指派订单（直接将订单指派到快递员身上）
				$res = CommonBusiness::receiveOrder( $qdata );
				break;
			case $msgTopic['curl'] :
				$res = CommonFun::queueCurl($qdata,1);
				break;
			case $msgTopic['mongo'] :
				$res = CommonFun::doMongoDB($qdata,1);
				break;
			default :
				break;
		}
		CommonFun::updateLogQueue($msgTopicId,$res);
		$payload = $this->getPayload ( $job );
		CommonFun::log ( '第' . $getAttempts . '次执行对列任务结果：' . json_encode($res,JSON_UNESCAPED_UNICODE) . '，数据：' . $payload, 'handle', 'baseListen' );
		return true;
	}
	
	/**
	 * 回调处理失败
	 * @author RTS 2017年2月6日 13:01:38
	 *
	 */
	public function failed($job, $data) {
		$payload = $this->getPayload ( $job );
		$getAttempts = $job->getAttempts ();
		CommonFun::log ( '第' . $getAttempts . '次执行对列任务失败，数据：' . $payload, 'failed', 'baseListen' );
		if (! $job->isDeleted ()) {
			//$job->delete ();
		}
		return false;
	}
	
	protected function getPayload($job) {
		$payload = $job->getPayload ();
		return json_encode ( unserialize ( $payload ), true );
	}
}
