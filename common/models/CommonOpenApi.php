<?php

namespace common\models;

use Yii;
use yii\base\Model;
use common\components\CommonFun;
use common\components\CommonValidate;

class CommonOpenApi extends Model {
	
	/**
	 * 下订单到openApi
	 * @author RTS 2017年2月8日 09:57:22
	 * @param string $data 数据集合
	 */
	public static function addOrder($data = []) {
		if(empty($data)){
			return false;
		}
		return self::curl($data);
	}
	
	/**
	 * 统一请求入口
	 * @author RTS 2017年2月8日 11:04:46
	 * @param unknown $data
	 * @param number $type
	 */
	private static function curl($data = [],$type = 0){
		$timestamp = CommonFun::getArrayValue($data,'timestamp','');
		$header [] = "Content-Type: application/json";
		$header [] = "timestamp:" . $timestamp;
		$openApi = Yii::$app->params['OpenApi'];
		$url = $openApi['host'];
		$list = $openApi['list'];
		switch ($type){
			case 0://发单
				$url .= $list['addorder'];
				break;
			case 1://取消订单
				$url .= $list['cancelorder'];
				break;	
		}
		$res = CommonFun::curlPost($url, json_encode($data),100,$header);
		CommonFun::log('URL：'.$url.'，结果：'.$res.'，data：'.json_encode($data,JSON_UNESCAPED_UNICODE),'curl','CommonOpenApi');
		
		return CommonValidate::isJson($res);
	}
	
	/**
	 * 取消订单
	 * @param unknown $data
	 * @return boolean
	 */
	public static function cancelOrder($data = []) {
		if(empty($data)){
			return false;
		}
		return self::curl($data,1);
	}
	
}
