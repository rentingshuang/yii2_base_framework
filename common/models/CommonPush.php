<?php

namespace common\models;

use Yii;
use yii\base\Model;

use common\components\CommonFun;

class CommonPush extends Model {
	
	/**
	 * 推送到附近
	 *
	 * @param type $goodsID        	
	 * @return type
	 */
	public static function pushOrder($goodsID = 0) {
		$str = explode ( '|', $goodsID );
		$goodid = $str [0];
		$type = $str [1]; // 1:普通订单 2：拼单订单
		$userid = $str [2]; // 商家ID号
		$data = [ ];
		$data ["reqName"] = "PushNearOrder";
		$data ["goodsid"] = $goodid;
		if ($type == 2) { // 获取拼单数据
			$dtSpellOrder = self::getFastSpellOrderDeatil ( $goodid );
			if (empty ( $dtSpellOrder )) {
				return false;
			}
			self::addNearBy ( $goodid, $dtSpellOrder ['Longitude'], $dtSpellOrder ['Latitude'], $userid, 4 );
			$data ["type"] = 4;
		} else if ($type == 1) {
			$payType = $str [3]; // 支付方式
			$couriers = $str [4]; // 指定自由人
			
			$isPackBus = self::checkIsPackBus ( $userid ); // 检查是否拼单商家
			if ($isPackBus && $payType == 4 && empty ( $couriers )) { // 指定接单是不参与自动拼单的
				$url = Yii::$app->params ['RRKDInterfaceHost'] . '/RRKDInterface/Interface/ohterInterface.php';
				$qarr = [ ];
				$qarr ["reqName"] = "addPacksQueue";
				$qarr ["goodsid"] = $goodid;
				$res = CommonFun::callInterface ( 'addPacksQueue', $qarr, $url );
				$qarr = json_encode ( $qarr, JSON_UNESCAPED_UNICODE );
				CommonFun::log ( "加入自动拼单队列：{$url}，传入参数：{$goodsID}，调用参数：{$qarr},调用结果：{$res}", 'addpacksqueue', 'addpacksqueue' );
				return $res;
			} else {
				$fastDeatil = self::getFastDeatil ( $goodid );
				if ($fastDeatil ['IsPay'] != 1 && $fastDeatil ['PayoffType'] == 4) { // 未支付且是余额付款
					return false;
				}
				$r = self::addNearBy ( $goodid, $fastDeatil ['Longitude'], $fastDeatil ['Latitude'], $userid, 1 );
				$data ["type"] = 1;
			}
		}
		$url = Yii::$app->params ['RRKDInterfaceHost'] . '/RRKDInterface/Interface/pushInterface.php';
		$res = CommonFun::callInterface ( 'PushNearOrder', $data, $url );
		$data = json_encode ( $data, JSON_UNESCAPED_UNICODE );
		CommonFun::log ( "推送地址：{$url}，传入参数：{$goodsID}，推送参数：{$data},推送结果：{$res}", 'push', 'push' );
		return $res;
	}
	
	
	/**
	 * 推送到仓配中心
	 * @param unknown $orderInfo
	 * @param unknown $orderProperty
	 * @param string $userName
	 */
	public static function push2Barn($orderInfo = [], $orderProperty = [], $userName = '') {
		$addressCode = CommonExtBCityCode::getAddressCode ( $orderInfo ['ReceiveProvince'], $orderInfo ['ReceiveCity'], '' );
		$siteCode = self::getSiteCodeInfo ( $userName );
		$publishData = [ 
				'sendName' => $orderInfo ['SendName'],
				'sendMobile' => $orderInfo ['SendMobile'],
				'sendProvince' => $orderInfo ['SendProvince'],
				'sendCity' => $orderInfo ['SendCity'],
				'sendAddress' => $orderInfo ['SendAddress'],
				'receiveName' => $orderInfo ['ReceiveName'],
				'receiveMobile' => $orderInfo ['ReceiveMobile'],
				'receiveProvince' => $orderInfo ['ReceiveProvince'],
				'receiveCity' => $orderInfo ['ReceiveCity'],
				'receiveAddress' => $orderInfo ['ReceiveAddress'],
				'isDefReceiveMobile' => CommonFun::getArrayValue ( $orderProperty, 'isDefReceiveMobile', 0 ),
				'isDefReceiveAddress' => CommonFun::getArrayValue ( $orderProperty, 'isDefReceiveAddress', 0 ),
				'barCode' => CommonFun::getArrayValue ( $orderInfo, 'BusinessRemark', '' ),
				'cityCode' => ! empty ( $addressCode ) ? $addressCode ['CityCode'] : '',
				'siteCode' => $siteCode,
				'ctime' => date ( 'Y-m-d H:i:s' ) 
		];
		return self::pushBarn(0,$userName,$publishData);
	}

	private  static function pushBarn($goodsID = 0, $userName = '', $data = []) {
		if (empty ( $data )) {
			$goodsInfo = self::getFastDeatil ( $goodsID );
			if (empty ( $goodsInfo )) {
				return false;
			}
			$addressCode =  CommonExtBCityCode::getAddressCode ( $goodsInfo ['ReceiveProvince'], $goodsInfo ['ReceiveCity'], '' );
			$siteCode = self::getSiteCodeInfo ( $userName );
			$data = [
				'sendName' => $goodsInfo ['SendName'],
				'sendMobile' => $goodsInfo ['SendMobile'],
				'sendProvince' => $goodsInfo ['SendProvince'],
				'sendCity' => $goodsInfo ['SendCity'],
				'sendAddress' => $goodsInfo ['SendAddress'],
				'receiveName' => $goodsInfo ['ReceiveName'],
				'receiveMobile' => $goodsInfo ['ReceiveMobile'],
				'receiveProvince' => $goodsInfo ['ReceiveProvince'],
				'receiveCity' => $goodsInfo ['ReceiveCity'],
				'receiveAddress' => $goodsInfo ['ReceiveAddress'],
				'isDefReceiveMobile' => 0,
				'isDefReceiveAddress' => 0,
				'barCode' => $goodsInfo ['BusinessRemark'],
				'cityCode' => ! empty ( $addressCode ) ? $addressCode ['CityCode'] : '',
				'siteCode' => $siteCode,
				'ctime' => date ( 'Y-m-d H:i:s' )
			];
		}
	
		$pres = Yii::$app->redis->lpush ( 'ldpAddOrder', json_encode ( $data ) );
		CommonFun::log ( '单条数据发单向对列ldpAddOrder写入结果：' . $pres . '，推送数据：' . json_encode ( $data, JSON_UNESCAPED_UNICODE ) . '订单数据：' . json_encode ( $data, JSON_UNESCAPED_UNICODE ), 'pushBarn', 'pushBarn' );
		return true;
	}
	
	/**
	 * 获取仓配站点
	 * @param string $userName
	 * @return string|Ambigous <string, NULL, \yii\db\false, \yii\db\mixed, mixed>
	 */
	public static function getSiteCodeInfo($userName = ''){
		$sql = "SELECT site_code from site_manager WHERE id= (SELECT rrkd_point_id FROM third_logistical_account WHERE username = :username AND is_del = 0 ORDER BY insert_date DESC LIMIT 1);";
		$db = Yii::$app->db;
		$cmd = $db->createCommand($sql)->bindParam(':username', $userName);
		$res = $cmd->queryScalar();
		if(empty($res)){
			return  '';
		}
		return $res;
	}
	
	
	/**
	 * 发布到附近
	 * @param type $goodsID
	 * @param type $longitude
	 * @param type $latitude
	 * @param type $userid
	 * @param type $nearbyType
	 */
	public static function addNearBy($goodsID = 0, $longitude, $latitude, $userid = 0, $nearbyType = 1) {
		$sql_arr = [];
		$sql_arr[] = " insert into fast_nearby( ";
		$sql_arr[] = " GoodsId,NearbyTpye,Lon,Lat,InsertDate,OrderType ";
		$sql_arr[] = "  )values( ";
		$sql_arr[] = "  :GoodsID,:NearbyTpye,:Longitude,:Latitude,NOW(),(SELECT case WHEN (SELECT IsSingleFlow from Business_AdvancedSettings where BusinessID=:BusinessID ORDER BY CreateDate DESC LIMIT 1)=0 THEN 1 ELSE 0 END as a)";
		$sql_arr[] = "  ); ";
		$sql = implode('', $sql_arr);
		$cmd = Yii::$app->db->createCommand($sql);
		$cmd->bindParam(':GoodsID', $goodsID);
		$cmd->bindParam(':NearbyTpye', $nearbyType);
		$cmd->bindParam(':Longitude', $longitude);
		$cmd->bindParam(':Latitude', $latitude);
		$cmd->bindParam(':BusinessID', $userid);
		return $cmd->execute() > 0;
	}
	
	/**
	 * 获取拼单快件详情
	 * @param type $goodsID
	 * @return type
	 */
	public static function getFastDeatil($goodsID = 0) {
		$sql = "select ReceiveProvince,ReceiveCity,SendProvince,SendCity,SendAddress,ReceiveName,ReceiveMobile,ReceiveProvince,ReceiveCity,ReceiveAddress,BusinessRemark,PayoffType,IsPay,Longitude,Latitude,GoodsName,SendName,SendMobile,CpId,IsPrecious,Status,CeId,CollectType,CourierId,PacksID  from fast_GoodsInfo where GoodsID = :GoodsID limit 1;";
		$res = Yii::$app->db->createCommand($sql)->bindParam(':GoodsID', $goodsID)->queryOne();
		if(empty($res)){//先找普通订单 找不到则找落地配订单库
			$res = Yii::$app->ldpdb->createCommand($sql)->bindParam(':GoodsID', $goodsID)->queryOne();
		}
		return $res;
	}
	
	/**
	 * 获取拼单快件[拼单]详情
	 * @param type $goodsID
	 * @return type
	 */
	public static function getFastSpellOrderDeatil($goodsID = 0) {
		$cacheName = "BusinessgetFastSpellOrderDeatil" . $goodsID;
		$cacheValue = CommonFun::getCache($cacheName);
		if (!empty($cacheValue)) {
			return $cacheValue;
		}
		$sql = "select Longitude,Latitude,PacksID  from fast_GoodsInfo_Packs where PacksID = :PacksID limit 1; ";
		$res = Yii::$app->db->createCommand($sql)->bindParam(':PacksID', $goodsID)->queryOne();
		if ($res != false) {
			CommonFun::setCache($cacheName, $res, 30);
		}
		return $res;
	}
	
	/**
	 * 检查商家是否支持拼单
	 * @param type $userId
	 * @return boolean
	 */
	public static function checkIsPackBus($userId = null) {
		if (empty($userId)) {
			return FALSE;
		}
		
		$cacheName = "BusinesscheckIsPackBus" . $userId;
		$cacheValue  = CommonFun::getCache($cacheName);
		if (!empty($cacheValue)) {
			return $cacheValue;
		}
		$sql = "SELECT IsSetAutoPack from BusinessInfo where ID=:ID ;";
		$res = Yii::$app->db->createCommand($sql)->bindParam(':ID', $userId)->queryOne();
		if ($res !== FALSE) {
			$isSetAutoPack = $res['IsSetAutoPack'] != 0;
			CommonFun::setCache($cacheName, $isSetAutoPack, 60);
			return $isSetAutoPack;
		}
		return false;
	}
}
