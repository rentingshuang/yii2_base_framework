<?php

namespace common\models;

use Yii;
use yii\base\Model;
use common\components\CommonFun;
use common\components\CommonValidate;
use PhpParser\Builder\FunctionTest;

class CommonBusiness extends Model {
	
	/**
	 * 商家发订单
	 *
	 * @author RTS 2017年2月21日 16:42:59
	 * @param array $data   	
	 * @return boolean
	 */
	public static function addOrder($data = []) {
		if (empty ( $data )) {
			return '缺少订单信息。';
		}
		try {
			$data['dataId'] = md5(serialize($data));//添加数据id 防止一条数据出现多次错误而记录多次mongoDB
			$needCalculatePrice = false; // 是否需要计算价格
			$orderProperty = CommonFun::getArrayValue ( $data, 'orderProperty', [ ] );
			$userInfo = CommonFun::getArrayValue ( $data, 'userInfo', [ ] );
			$orderInfo = CommonFun::getArrayValue ( $data, 'orderInfo', [ ] );
			$expandInfo = CommonFun::getArrayValue ( $data, 'expandInfo', [ ] );//订单的扩展信息 目前有价格的组成之类的 调用方需自己组合扩展信息传入 不然扩展信息是没有的
			
			if (empty ( $orderInfo )) {
				return '缺少订单信息！';
			}
			
			if (! empty ( $orderProperty )) {
				$needCalculatePrice = CommonFun::getArrayValue ( $orderProperty, 'isCalculatePrice', false );
			}
			$orderType = CommonFun::getArrayValue ( $orderProperty, 'type', 0 ); // 0 普通订单 需要校验业务范围 1 落地配 则不需要校验
			$isErrLog = CommonFun::getArrayValue ( $orderProperty, 'isErrLog', 0 );
			$payType = CommonFun::getArrayValue ( $orderInfo, 'PayoffType', 4 );
			$userId = CommonFun::getArrayValue ( $userInfo, 'userId', 0 );
			$userName = CommonFun::getArrayValue ( $userInfo, 'userName', 'unkonw' );
			
			
			$cacheName = 'data-'.$data['dataId'];
			$cacheValue = CommonFun::getCache ( $cacheName );
			if (! empty($cacheValue)){
				return '订单处理中，请稍候再试。';
			}
			CommonFun::setCache($cacheName,1,2);
			
			$res = self::checkOrderData($orderInfo,$orderType);
			if($res !== true){
				if ($isErrLog) {
					$data ['errMsg'] = $res;
					self::errLog ( $data );
				}
				return $res;
			}
			
			if ($needCalculatePrice) {
				$res = self::calculatePrice ( $orderInfo, $userInfo, $orderType );
				if (is_string ( $res )) {
					if ($isErrLog) {
						$data ['errMsg'] = $res;
						self::errLog ( $data );
					}
					return $res;
				}
				
				$manageFreightArray = $res ['manageFreightArray'];
				$locationArray = $res ['locationArray'];
				
				$serviceFees = CommonFun::getArrayValue ( $orderInfo, 'ServiceFees', 0 );
				$goodsMoney = CommonFun::getArrayValue ( $manageFreightArray, 'finalGoodsMoney', 0 );
				$goodsMoney = $goodsMoney + $serviceFees;
				$platformGoodsMoney = CommonFun::getArrayValue ( $manageFreightArray, 'freightmoney', 0 );
				$platformGoodsMoney = $platformGoodsMoney + $serviceFees;
				$goodsServicePrice = CommonFun::getArrayValue ( $manageFreightArray, 'servicefee', 0 ); // 服务费
				                                                                                        
				// 区域范围时效
				$orderInfo ['GoodsMoney'] = $platformGoodsMoney;
				$orderInfo ['DeliveryLimitTxt'] = $manageFreightArray ["timetext"];
				$orderInfo ['DeliveryLimit'] = $manageFreightArray ["tid"];
				$orderInfo ['DeliveryLimitType'] = $manageFreightArray ["userpricetype"];
				$orderInfo ['DeliveryLimitValue'] = $manageFreightArray ["timevalue"];
				
				// 商家自定义时效
				$orderInfo ['GoodsServicePrice'] = $goodsServicePrice;
				$orderInfo ['UserGoodsMoney'] = $goodsMoney;
				$orderInfo ['UserDeliveryLimitTxt'] = $manageFreightArray ["timetext"];
				$orderInfo ['UserDeliveryLimit'] = $manageFreightArray ["timevalue"];
				$orderInfo ['UserPriceType'] = $manageFreightArray ["userpricetype"];
				$orderInfo ['UserPriceID'] = $manageFreightArray ["tid"];
				
				$sphereID = CommonFun::getArrayValue ( $locationArray, 'SphereID', 0 );
				$goodsCost = CommonFun::getArrayValue ( $orderInfo, 'GoodsCost', 1 ); // 是否需要冻结
				$isPrecious = self::checkIsPreciousBySphereOfBusiness ( $goodsCost, $sphereID );
				
				
				$orderInfo ['IsPay'] = ($payType == 1 ? 1 : 0);
				$orderInfo ['EndLon'] = $locationArray ["ReceiveLon"];
				$orderInfo ['EndLat'] = $locationArray ["ReceiveLat"];
				
				$orderInfo ['Distance'] = ($locationArray ["DistanceData"] / 1000); // 距离
				$orderInfo ['IsPrecious'] = intval ( $isPrecious );
				$orderInfo ['IsNight'] = $manageFreightArray ["isnight"] ? 1 : 0; // 是否为夜间件(1是0否)
				
				
				$cfgPicktime = CommonFun::getArrayValue ( $manageFreightArray, 'pickUpTime', 20 ); 
				$expandInfo = [
					'cfg_picktime' => $cfgPicktime,
				];
				$ext = self::getExtInfo($manageFreightArray);
				$expandInfo = array_merge($expandInfo,$ext);
			}
			
			$isLoan = CommonFun::getArrayValue ( $orderProperty, 'isLoan', 0 );//是否垫资账户
			$dicRet = self::send ( $orderInfo, $userInfo, $expandInfo, $orderType, $isLoan );

			$goodsID = $dicRet ["GoodsID"];
			if ($goodsID <= 0) {
				$errMsg = CommonFun::getArrayValue($dicRet,'Msg','发货失败，请稍候再试。');
				if ($isErrLog) {
					$data ['errMsg'] = $errMsg;
					$data ['errDesc'] = $errMsg.json_encode($dicRet,JSON_UNESCAPED_UNICODE);
					self::errLog ( $data );
				}
				return  json_encode($dicRet,JSON_UNESCAPED_UNICODE);;
			}
			
			if ($dicRet ['IsPay']) {
				$isPush = CommonFun::getArrayValue ( $orderProperty, 'isPush', 1 ); 
				$courierId = CommonFun::getArrayValue ( $orderProperty, 'courierId', '' );
				if ($isPush) {// 推送到附近 //TODO 可能需要做异步处理
					$params = $goodsID . "|1|" . $userId . "|" . $payType . "|" . $courierId;
					$result = CommonPush::pushOrder ( $params );
					CommonFun::log ( '单条数据发单成功，结果：' . json_encode ( $dicRet, JSON_UNESCAPED_UNICODE ), 'addOrder', $userName );
					CommonFun::log ( '单条下单已触发推送，推送参数：[' . $params . '] 推送结果：' . json_encode ( $result ), 'addOrder', $userName );
				}
				
				$isPush2Barn = CommonFun::getArrayValue ( $orderProperty, 'isPush2Barn', 0 );
				if($isPush2Barn){
					CommonPush::push2Barn($orderInfo,$orderProperty,$userName);
				}
			}
		}
		catch (\Exception $e) {
			return '发单失败，请稍候再试。';//$e->getMessage().$e->getFile().'->'.$e->getLine().'->'.$e->getMessage();
		
		}
		return $dicRet;
	}
	
	/**
	 * 下单
	 * @author RTS 2017年2月22日 14:45:42
	 * @param array $orderInfo 订单信息
	 * @param array $userInfo 用户信息
	 * @param array $expandInfo 订单扩展信息
	 * @param number $orderType 订单类型 0 普通 1 落地配(需要入新库)
	 * @param number $isLoan 是否垫资账户
	 * @return array
	 */
	private static function send($orderInfo = [], $userInfo = [], $expandInfo = [], $orderType = 0, $isLoan = 0) {
		$GoodsID = 0;
		$db = Yii::$app->db;
		$ldpDb = Yii::$app->ldpdb;
		
		$IsPay = true;
		$Result ["IsPay"] = $IsPay;
		$AccountsID = CommonFun::getArrayValue($userInfo,'accountsId',0);
		$UserId = CommonFun::getArrayValue($userInfo,'userId',0);
		$UserName = CommonFun::getArrayValue($userInfo,'userName',0);
		$newDataBase = $orderType == 1;

		$sbSql = [ ];
		$goodsNum = 0;
		$payMoney = $orderInfo ['UserGoodsMoney'] - $orderInfo ['ServiceFees'];
		$ConponMoney = 0;
		$Result ["conponMoney"] = $ConponMoney;
		$Result ["payMoney"] = $payMoney;
		$Result ["UserGoodsMoney"] = $orderInfo ['UserGoodsMoney'];
		
		$now = date ( 'Y-m-d H:i:s' );
		$orderInfo ['SendDate'] = CommonFun::getArrayValue ( $orderInfo, 'SendDate', $now );
		$orderInfo ['InsertDate'] = CommonFun::getArrayValue ( $orderInfo, 'InsertDate', $now );
		
		//$trans = $db->beginTransaction ();//老的事务
		//$transLdp = $ldpDb->beginTransaction ();//新的事务
		try {
			$PayoffType = $orderInfo ['PayoffType'];
			if (! in_array ( $PayoffType, [ 2,4,5 ] )) {
				$Result ["GoodsID"] = - 999;
				return $Result;
			}

			$orderInfo ['PayoffType'] = $PayoffType == 5 ? 4 : $orderInfo ['PayoffType'];
			$goodsNum = CommonFun::getOrderNumber();
			if(isset($orderInfo['ClaimPickupDate']) && empty($orderInfo['ClaimPickupDate'])){
				unset($orderInfo['ClaimPickupDate']);
			}
			
			$orderInfo ['GoodsNum'] = $orderType == 0 ? $goodsNum : ('10' . $goodsNum);
			$sqlArr = CommonFun::createSql ( $orderInfo );
			$sql = "INSERT INTO fast_GoodsInfo(" . $sqlArr ['fields'] . ") VALUES (" . $sqlArr ['values'] . ')';
			$cmd = $newDataBase? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
			$cmd->bindValues ( $sqlArr ['params'] );
			$res = $cmd->execute ();
			if ($res < 1) {
				$Result ["GoodsID"] = - 1;
				return $Result;
			}
			$GoodsID = $newDataBase ? $ldpDb->getLastInsertID () : $db->getLastInsertID ();
			
			if (! empty ( $expandInfo )) {
				$expandInfo ['id'] = $GoodsID;
				$expandInfo ['insert_date'] = date ( 'Y-m-d H:i:s' );
				$expandInfo ['update_date'] = $expandInfo ['insert_date'];
				$expandInfo ['order_source'] = self::getBusinessSendhelperId ( $UserId );
				
				/**
				 * **********加价和优惠卷记录************
				 */
				$expandInfo ['add_fee'] = CommonFun::getArrayValue ( $orderInfo, 'ServiceFees', 0 ); // 加价
				if (false) {
					//$expandInfo ['coupon_number'] = $CouponNo;
					//$expandInfo ['coupon_fee'] = $CouponMoney;
				}
				
				$sqlArr = CommonFun::createSql ( $expandInfo );
				$sql = "INSERT INTO fast_goods_external(" . $sqlArr ['fields'] . ") VALUES (" . $sqlArr ['values'] . ')';
				$cmd = $newDataBase? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
				$cmd->bindValues ( $sqlArr ['params'] );
				$res = $cmd->execute ();
				if ($res < 1) {
					throw new \Exception('写入扩展表失败。');
				}
			}
			
			// 当快件为余额支付时，进入余额支付流程
			if ($PayoffType == 4 && $payMoney != 0) {
				$payMoney = $payMoney + $orderInfo ['ServiceFees'];
				
				// 快件运费
				$UserGoodsMoney = $payMoney; // $GoodsInfo['UserGoodsMoney'];
				$Balance = 0; // 总额
				$Prepaid_Amount = 0; // 充值
				$Give_Amount = 0; // 赠送
				$Freight_Amount = 0; // 运费
				$Subsidy_Amount = 0; // 补贴
				$Loan_Amount = 0; // 平台垫资余额
				$sql = "select Prepaid_Amount,Give_Amount,Freight_Amount,Subsidy_Amount,Loan_Amount from RRKD_Accounts where Business_UserID = :UserID ";
				$res = $db->createCommand ( $sql )->bindParam ( ':UserID', $UserId )->queryOne ();
				if (empty ( $res )) {
					throw new \Exception('查询金额失败。');
				}
				
				$Prepaid_Amount = floatval ( $res ['Prepaid_Amount'] ); // 充值
				$Give_Amount = floatval ( $res ['Give_Amount'] ); // 赠送
				$Freight_Amount = floatval ( $res ['Freight_Amount'] ); // 运费
				$Subsidy_Amount = floatval ( $res ['Subsidy_Amount'] ); // 补贴
				$Loan_Amount = floatval ( $res ['Loan_Amount'] ); // 平台垫资余额
				if ($isLoan) {
					$Balance = $Loan_Amount;
				} else {
					$Balance = $Prepaid_Amount + $Give_Amount + $Freight_Amount + $Subsidy_Amount;
				}
				// egion 2、根据账户余额和运费判断是否满足余额支付，不满足快件进入待支付状态
				if ($Balance >= $UserGoodsMoney) {
					// 优先扣除 充值 > 赠送金 > 运费 > 补贴
					$Reduce_Prepaid_Amount = 0; // 需要扣除的充值金额
					$Reduce_Give_Amount = 0; // 需要扣除的赠送金额
					$Reduce_Freight_Amount = 0; // 需要扣除的运费金额
					$Reduce_Subsidy_Amount = 0; // 需要扣除的补贴金额
					$Reduce_Loan_Amount = 0; // 公司垫资金额
					if ($isLoan) {
						if ($Loan_Amount >= $UserGoodsMoney) {
							$Reduce_Loan_Amount = $UserGoodsMoney;
						}
					} else {
						if ($Prepaid_Amount >= $UserGoodsMoney) {
							$Reduce_Prepaid_Amount = $UserGoodsMoney;
						} else if (($Prepaid_Amount + $Give_Amount) >= $UserGoodsMoney) {
							$Reduce_Prepaid_Amount = $Prepaid_Amount;
							$Reduce_Give_Amount = $UserGoodsMoney - $Prepaid_Amount;
						} else if (($Prepaid_Amount + $Give_Amount + $Freight_Amount) >= $UserGoodsMoney) {
							$Reduce_Prepaid_Amount = $Prepaid_Amount;
							$Reduce_Give_Amount = $Give_Amount;
							$Reduce_Freight_Amount = $UserGoodsMoney - $Prepaid_Amount - $Give_Amount;
						} else {
							$Reduce_Prepaid_Amount = $Prepaid_Amount;
							$Reduce_Give_Amount = $Give_Amount;
							$Reduce_Freight_Amount = $Freight_Amount;
							$Reduce_Subsidy_Amount = $UserGoodsMoney - $Prepaid_Amount - $Give_Amount - $Freight_Amount;
						}
					}
					
					$where[] = " Business_UserID = :UserID ";
					$update = [];
					$bindParams =[':UserID'=>$UserId];
					
					$Reduce_Prepaid_Amount = floatval($Reduce_Prepaid_Amount);
					$Reduce_Give_Amount = floatval($Reduce_Give_Amount);
					$Reduce_Freight_Amount = floatval($Reduce_Freight_Amount);
					$Reduce_Subsidy_Amount = floatval($Reduce_Subsidy_Amount);
					
					if($Reduce_Prepaid_Amount > 0){
						$update[] =" Prepaid_Amount = Prepaid_Amount - :Reduce_Prepaid_Amount";
						$where[] ="  Prepaid_Amount - :Reduce_Prepaid_Amount >= 0 ";
						$bindParams[':Reduce_Prepaid_Amount'] = $Reduce_Prepaid_Amount;
					}
					if($Reduce_Give_Amount > 0){
						$update[] =" Give_Amount = Give_Amount - :Reduce_Give_Amount";
						$where[] =" Give_Amount - :Reduce_Give_Amount >= 0 ";
						$bindParams[':Reduce_Give_Amount'] = $Reduce_Give_Amount;
					}
					
					if($Reduce_Freight_Amount > 0){
						$update[] =" Freight_Amount = Freight_Amount - :Reduce_Freight_Amount";
						$where[] ="  Freight_Amount - :Reduce_Freight_Amount >= 0 ";
						$bindParams[':Reduce_Freight_Amount'] = $Reduce_Freight_Amount;
					}
					
					if($Reduce_Subsidy_Amount > 0){
						$update[] =" Subsidy_Amount = Subsidy_Amount - :Reduce_Subsidy_Amount";
						$where[] =" Subsidy_Amount - :Reduce_Subsidy_Amount >= 0 ";
						$bindParams[':Reduce_Subsidy_Amount'] = $Reduce_Subsidy_Amount;
					}
					
					if($Reduce_Loan_Amount > 0){
						$update[] =" Loan_Amount = Loan_Amount - :Reduce_Loan_Amount";
						$where[] =" Loan_Amount - :Reduce_Loan_Amount >= 0 ";
						$bindParams[':Reduce_Loan_Amount'] = $Reduce_Loan_Amount;
					}
					
					$update_str = implode(',', $update);
					$where_str =  implode(' and ', $where);
					$sql = "UPDATE RRKD_Accounts Set {$update_str}  where {$where_str}";
					
					if ($UserGoodsMoney != 0) { // 处理优惠后应付为0 的情况 mysql返回0
						$cmd = $db->createCommand($sql);
						$cmd->bindValues($bindParams);
						$res = $cmd->execute();
						if ($res < 1) {
							throw new \Exception('更新用户余额失败。');
						}
					}
					
					$sql = "select Prepaid_Amount,Give_Amount,Freight_Amount,Subsidy_Amount,Loan_Amount from RRKD_Accounts where Business_UserID = :UserID ";
					$res = $db->createCommand ( $sql )->bindParam ( ':UserID', $UserId )->queryOne ();
					$sql = "INSERT RRKD_Accounts_TransactionDetails_Log (AccountsID,TransactionType,OrderID,Amount,Prepaid_Amount,Give_Amount,Freight_Amount,Subsidy_Amount,Loan_Amount,InsertDate,Source)
VALUES(:AccountsID,2,:OrderID,:Amount,:Prepaid_Amount,:Give_Amount,:Freight_Amount,:Subsidy_Amount,:Loan_Amount,now(),'sendOrder');";
					
					$cmd = $db->createCommand ( $sql );
					$cmd->bindParam ( ':AccountsID', $AccountsID );
					$cmd->bindParam ( ':OrderID', $GoodsID );
					$cmd->bindParam ( ':Amount', $UserGoodsMoney );
					$cmd->bindParam ( ':Prepaid_Amount', $res ['Prepaid_Amount'] );
					$cmd->bindParam ( ':Give_Amount', $res ['Give_Amount'] );
					$cmd->bindParam ( ':Freight_Amount', $res ['Freight_Amount'] );
					$cmd->bindParam ( ':Subsidy_Amount', $res ['Subsidy_Amount'] );
					$cmd->bindParam ( ':Loan_Amount', $res ['Loan_Amount'] );
					
					$res = $cmd->execute ();
					if ($res < 1) {
						throw new \Exception('查询余额失败！');
					}
					
					// 添加消费流水表
					$Reduce_Money = [ ];
					$Reduce_Money [] = $Reduce_Prepaid_Amount;
					$Reduce_Money [] = $Reduce_Give_Amount;
					$Reduce_Money [] = $Reduce_Freight_Amount;
					$Reduce_Money [] = $Reduce_Subsidy_Amount;
					
					$Reduce_Money [8] = $Reduce_Loan_Amount;
					$sql = "insert into RRKD_Accounts_TransactionDetails(AccountsID,Number,TransactionType,Description,PaySource,Amount,InsertDate,Deled,OrderID,ExpenseType )VALUE(:AccountsID,UUID(),2,:Description,5,:Amount,NOW(),0,:GoodsID,:ExpenseType );";
					$Description = '发单扣除';
					foreach ( $Reduce_Money as $k => $item ) {
						$ExpenseType = $k + 1;
						if ($item != 0) {
							$cmd = $db->createCommand ( $sql );
							$cmd->bindParam ( ':AccountsID', $AccountsID );
							$cmd->bindParam ( ':Description', $Description );
							$cmd->bindParam ( ':Amount', $item );
							$cmd->bindParam ( ':GoodsID', $GoodsID );
							$cmd->bindParam ( ':ExpenseType', $ExpenseType );
							$res = $cmd->execute ();
							if ($res < 1) {
								throw new \Exception('插入明细表失败！');
							}
						}
					}
					
					// 修改快件支付状态为已支付
					$sql = "update fast_GoodsInfo set IsPay = 1 , PayDate=NOW()  where GoodsId = :GoodsID; ";
					$cmd = $newDataBase ? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
					$cmd->bindParam ( ':GoodsID', $GoodsID );
					$res = $cmd->execute ();
					if ($res < 1) {
						throw new \Exception('更新支付状态失败！');
					}
					$businessRemark = CommonFun::getArrayValue($orderInfo,'businessRemark','');
					if (! empty ( $businessRemark )) {
						self::addBarCode ( $businessRemark, $GoodsID );
					}
				} else { // 余额不足,修改快件支付状态未未支付
					if ($isLoan || $newDataBase) { // 公司垫资和落地配则不进待支付 
						throw new \Exception((($isLoan?'垫资':'').'余额不足！'));
					}
					
					$sql = "update fast_GoodsInfo set IsPay = 0 , PayDate=NOW()  where GoodsId = :GoodsID; ";
					$cmd = $newDataBase ? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
					$cmd->bindParam ( ':GoodsID', $GoodsID );
					$res = $cmd->execute ();
					$IsPay = false;
					if ($res < 1) {
						throw new \Exception('更新订单状态失败！');
					}
				}
			} elseif ($PayoffType == 5 && $payMoney != 0) { // 微信支付特殊标识
				$sql = "update fast_GoodsInfo set IsPay = 0,OnilinPayType = 6 where GoodsId = :GoodsID; ";
				$cmd = $newDataBase ? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
				$cmd->bindParam ( ':GoodsID', $GoodsID );
				$res = $cmd->execute ();
				$IsPay = false;
				if ($res < 1) {
					throw new \Exception('更新订单状态失败！！');
				}
			}
			
			// egion 1、添加下单成功日志
			$sql = "insert into fast_GoodsLog(GoodsId,TypeID,OperatingNote,InsertDate,UserType,UserId )values(:GoodsId,:TypeID,:OperatingNote,NOW(),1,:SenderId  ); ";
			$OperatingNote = $IsPay ? "用户下单" : "用户下单(待付款)";
			$TypeID = 1;
			$cmd = $db->createCommand ( $sql );
			$cmd = $newDataBase ? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
			
			$cmd->bindParam ( ':GoodsId', $GoodsID );
			$cmd->bindParam ( ':SenderId', $orderInfo ['SenderId'] );
			$cmd->bindParam ( ':OperatingNote', $OperatingNote );
			$cmd->bindParam ( ':TypeID', $TypeID );
			$res = $cmd->execute ();
			if ($res < 1) {
				throw new \Exception('添加下单失败！');
			}
			// egion 2、根据支付状态分别添加发布到平台或等待支付日志
			$sql = " insert into fast_GoodsLog(GoodsId,TypeID,OperatingNote,InsertDate,UserType,UserId )values(:GoodsId,:TypeID,:OperatingNote,NOW(),1,:SenderId  ); ";
			$TypeID = $IsPay ? 10 : 30;
			$OperatingNote = $IsPay ? "寄件人发布到平台" : "等待支付";
			$cmd = $db->createCommand ( $sql );
			$cmd = $newDataBase ? $ldpDb->createCommand ( $sql ) : $db->createCommand ( $sql );
			
			$cmd->bindParam ( ':GoodsId', $GoodsID );
			$cmd->bindParam ( ':SenderId', $orderInfo ['SenderId'] );
			$cmd->bindParam ( ':OperatingNote', $OperatingNote );
			$cmd->bindParam ( ':TypeID', $TypeID );
			$res = $cmd->execute ();
			if ($res < 1) {
				throw new \Exception('添加下单失败！！');
			}
			//$trans->commit ();
			//$transLdp->commit ();
			$Result ["GoodsID"] = $GoodsID;
			$Result ["IsPay"] = $IsPay;
			return $Result;
		} catch ( \Exception $exc ) {
			//$trans->rollBack ();
			//$transLdp->rollBack ();
			$Result ["GoodsID"] = 0;
			$Result ["IsPay"] = false;
			$Result ["Msg"] = "发单失败：" . $exc->getMessage ();
			return $Result;
		}
	}
	
	
	/**
	 * 校验数据合法性
	 * @author RTS 2017年2月24日 10:08:24
	 * @param array $data
	 * @param int $orderType 0 普通 1 落地配（需要校验条形码）
	 */
	public static function checkOrderData($orderInfo = [], $orderType = 0) {
		if (empty ( $orderInfo )) {
			return '数据为空';
		}
		
		
		$receiveProvince = CommonFun::getArrayValue ( $orderInfo, 'ReceiveProvince', '' );
		$receiveCity = CommonFun::getArrayValue ( $orderInfo, 'ReceiveCity', '' );
		$receiveCounty = CommonFun::getArrayValue ( $orderInfo, 'ReceiveCounty', '' );
		
		$receiveAddress = CommonFun::getArrayValue ( $orderInfo, 'ReceiveAddress', '' );
		$goodsCost = intval ( CommonFun::getArrayValue ( $orderInfo, 'GoodsCost', 0 ) );
		$goodsWeight = intval ( CommonFun::getArrayValue ( $orderInfo, 'GoodsWeight', 0 ) );
		
		$transport = intval ( CommonFun::getArrayValue ( $orderInfo, 'Transport', 0 ) );
		$claimPickupDate = CommonFun::getArrayValue ( $orderInfo, 'ClaimPickupDate', '' );
		$goodsName = CommonFun::getArrayValue ( $orderInfo, 'GoodsName', '' );
		
		$receiveName = CommonFun::getArrayValue ( $orderInfo, 'ReceiveName', '' );
		$receiveMobile = CommonFun::getArrayValue ( $orderInfo, 'ReceiveMobile', '' );
		
		$sendName = CommonFun::getArrayValue ( $orderInfo, 'SendName', '' );
		$sendMobile = CommonFun::getArrayValue ( $orderInfo, 'SendMobile', '' );
		$sendProvince = CommonFun::getArrayValue ( $orderInfo, 'SendProvince', '' );
		$sendCity = CommonFun::getArrayValue ( $orderInfo, 'SendCity', '' );
		$sendAddress = CommonFun::getArrayValue ( $orderInfo, 'SendAddress', '' );
		
		if (empty ( $goodsName ) || strlen ( $goodsName ) > 50) {
			return '物品名称 [' . $goodsName . '] 错误';
		}
		if (empty ( $receiveProvince ) || empty ( $receiveCity ) || empty ( $receiveAddress )) {
			return '收货地 省-市-地[' . $receiveProvince . '-' . $receiveCity . '-' . $receiveAddress . '] 不完整';
		}
		if (empty ( $receiveName ) || strlen ( $receiveName ) > 50) {
			return '收货人 姓名[' . $receiveName . '] 错误。';
		}
		if (empty ( $receiveName ) || empty ( $receiveMobile ) || ! CommonValidate::isMobile ( $receiveMobile )) {
			return '收货人 姓名-电话[' . $receiveName . '-' . $receiveMobile . '] 错误';
		}
		if (empty ( $goodsCost ) || $goodsCost <= 0 || $goodsCost > 5000) {
			return '货物价值 [' . $goodsCost . '] 错误';
		}
		if (empty ( $goodsWeight ) || $goodsWeight <= 0 || $goodsWeight > 100) {
			return '物品重量 [' . $goodsWeight . '] 错误';
		}
		if (empty ( $sendName ) || strlen ( $sendName ) > 50) {
			return '发货人 姓名[' . $sendName . '] 错误。';
		}
		if (empty ( $sendProvince ) || empty ( $sendCity ) || empty ( $sendAddress )) {
			return '发货地 省-市-地[' . $sendProvince . '-' . $sendCity . '-' . $sendAddress . '] 不完整';
		}
		
		if (empty ( $sendMobile ) || empty ( $sendMobile ) || ! CommonValidate::isMobile ( $sendMobile )) {
			return '发货人 姓名-电话[' . $sendName . '-' . $sendMobile . '] 错误';
		}
		
		if ($orderType == 1) { // 如果是落地配 需要判断条形码是否为空 或是否已经存在
			$businessRemark = trim( CommonFun::getArrayValue ( $orderInfo, 'BusinessRemark', '' ));
			$res = self::existForBarCode ( $businessRemark );
			if (is_string($res)) {
				return $res;
			}
		}
		return true;
	}
	
	/**
	 * 记录计价失败或者发单失败的记录
	 * 
	 * @author RTS 2017年2月22日 10:59:30
	 * @param array $data        	
	 */
	public static function errLog($data = '') {
		CommonFun::log ( json_encode ( $data, JSON_UNESCAPED_UNICODE ), __FUNCTION__, 'CommonBusiness' );
		$mongodb = Yii::$app->mongodb;
		$mongoName = date ( 'Y-m' ) . '-order-err';
		$collection = $mongodb->getCollection ( $mongoName );
		$where = [
			'dataId' => CommonFun::getArrayValue($data,'dataId','')
		];
		$option = ['upsert'=>true];
		$collection->update ( $where, $data,$option );
		$mongodb->close ();
	}
	
	/**
	 * 计算价格
	 * 
	 * @author RTS 2017年2月22日 11:07:50
	 * @param
	 *        	$data
	 */
	public static function calculatePrice($orderData = [], $userInfo = [], $orderType = 0) {
		if (empty ( $orderData )) {
			return '计价数据为空';
		}
		$serviceFees = CommonFun::getArrayValue ( $orderData, 'ServiceFees', 0 );
		$userId = CommonFun::getArrayValue ( $userInfo, 'userId', 0 );
		$sendProvince = CommonFun::getArrayValue ( $orderData, 'SendProvince', '' );
		$sendCity = CommonFun::getArrayValue ( $orderData, 'SendCity', '' );
		$sendAddress = CommonFun::getArrayValue ( $orderData, 'SendAddress', '' );
		
		$receiveProvince = CommonFun::getArrayValue ( $orderData, 'ReceiveProvince', '' );
		$receiveCity = CommonFun::getArrayValue ( $orderData, 'ReceiveCity', '' );
		$receiveAddress = CommonFun::getArrayValue ( $orderData, 'ReceiveAddress', '' );
		
		$goodsWeight = CommonFun::getArrayValue ( $orderData, 'GoodsWeight', 1 );
		$goodsCost = CommonFun::getArrayValue ( $orderData, 'GoodsCost', 1 );
		$transport = CommonFun::getArrayValue ( $orderData, 'Transport', 0 );
		$claimPickupDate = CommonFun::getArrayValue ( $orderData, 'ClaimPickupDate', '' );
		
		$payType = CommonFun::getArrayValue ( $orderData, 'PayoffType', 4 );
		$userFreightArray = $locationArray = $manageFreightArray = null;
		$orderType = ! $orderType;
		
		$sendLocationArray = $receiveLocationArray = null;
		$lon = CommonFun::getArrayValue ( $orderData, 'Longitude', '' );
		$lat = CommonFun::getArrayValue ( $orderData, 'Latitude', '' );
		if (! empty ( $lat ) && ! empty ( $lon )) {
			$sendLocationArray ['lon'] = $lon;
			$sendLocationArray ['lat'] = $lat;
		}
		
		$lon = CommonFun::getArrayValue ( $orderData, 'EndLon', '' );
		$lat = CommonFun::getArrayValue ( $orderData, 'EndLat', '' );
		if (! empty ( $lat ) && ! empty ( $lon )) {
			$receiveLocationArray ['lon'] = $lon;
			$receiveLocationArray ['lat'] = $lat;
		}
		
		$msg = CommonAddressAndFreight::orderFreightCalculation ( $serviceFees, $userId, $sendProvince, $sendCity, '', $sendAddress, $receiveProvince, $receiveCity, '', $receiveAddress, $goodsWeight, $goodsCost, $transport, $claimPickupDate, $userFreightArray, $manageFreightArray, $locationArray, $receiveLocationArray, $sendLocationArray, $orderType );
		if (! empty ( $msg )) {
			return $msg;
		}
		
		$platformGoodsMoney = CommonFun::getArrayValue ( $manageFreightArray, 'freightmoney', 0 ); // 区域价格
		$userGoodsMoney = CommonFun::getArrayValue ( $manageFreightArray, 'freightmoney2', 0 ); // 商家自定义配置价格
		$goodsMoney = 0; // 用户最总支付的金额
		$res = self::getPayMethod ( $userId ); // 查询是否存在直推价格
		$pushPrice = ! empty ( $res ['DirectPushprices'] ) ? $res ['DirectPushprices'] : 0;
		$isSpecifiedPrice = self::checkBusinessPayType ( $userId, $payType ); // 是否应走商家特定价格
		if ($platformGoodsMoney == 0 && $userGoodsMoney != 0) {
			$goodsMoney = $userGoodsMoney;
			$platformGoodsMoney = $userGoodsMoney;
		} else if ($platformGoodsMoney != 0 && $userGoodsMoney == 0) {
			$goodsMoney = $platformGoodsMoney;
		} else if ($pushPrice != 0) {
			if ($pushPrice == 1) {
				if ($isSpecifiedPrice) {
					if ($userGoodsMoney > 0) {
						$goodsMoney = $userGoodsMoney;
						$platformGoodsMoney = $userGoodsMoney;
					} else {
						$goodsMoney = $platformGoodsMoney;
					}
				} else {
					$goodsMoney = $platformGoodsMoney;
				}
			} else if ($pushPrice == 2) { // 直推平台价格
				$goodsMoney = (($isSpecifiedPrice && $userGoodsMoney > 0) ? $userGoodsMoney : $platformGoodsMoney);
			}
		} else {
			$goodsMoney = (($isSpecifiedPrice && $userGoodsMoney > 0) ? $userGoodsMoney : $platformGoodsMoney);
		}
		
		$manageFreightArray ["freightmoney"] = $platformGoodsMoney;
		$manageFreightArray ["finalGoodsMoney"] = $goodsMoney;
		
		return [ 
				'manageFreightArray' => $manageFreightArray,
				'locationArray' => $locationArray 
		];
	}
	
	/**
	 * 获取商家基本信息
	 *
	 * @param number $id        	
	 * @return Ambigous <multitype:, \yii\db\false, \yii\db\mixed, mixed>|boolean
	 */
	public static function getBusinessInfo($id = 0) {
		$sql = "select ID,UserName,BusinessName,Province,City,District,Address,Contact,FixedTelephone,MobileNumber,BusinessLicense,InsertDate,Enabled,PassWord,Logo,LastLoginTime,LastLoginIP,Introduce,Brand,Pdatype,Longitude,Latitude,BusinessType,CircleID,isStop,StopDate,OtherPrice,OtherShopID,bfb,IdentityCard,ClassID from BusinessInfo where ID=:ID and BusinessType in (0,3)";
		$res = Yii::$app->db->createCommand ( $sql )->bindParam ( ':ID', $id )->queryOne ();
		if ($res !== FALSE) {
			return $res;
		}
		return FALSE;
	}
	
	/**
	 * 是否是落地配商户
	 *
	 * @param string $userName        	
	 * @return boolean
	 */
	public static function checkInSphereOfBusiness($userName = '') {
		if (empty ( $userName )) {
			return false;
		}
		
		$sql = "SELECT COUNT(0) as cn FROM third_logistical_account WHERE username = :username and is_del = 0;";
		$db = Yii::$app->db;
		$cmd = $db->createCommand ( $sql )->bindParam ( ':username', $userName );
		$res = $cmd->queryScalar ();
		
		if ($res == 1) {
			return true;
		}
		if (! isset ( Yii::$app->params ['NoCheckUser'] )) {
			return false;
		}
		$list = Yii::$app->params ['NoCheckUser'] ['list'];
		if (in_array ( $userName, $list )) {
			return true;
		}
		return false;
	}
	
	/**
	 * 商家是否直推价
	 *
	 * @param number $userId        	
	 */
	public static function getPayMethod($userId = 0) {
		$sql = "SELECT * FROM Business_PayMethod WHERE BusinessInfoID = :BusinessInfoID LIMIT 1;";
		$db = Yii::$app->db;
		$cmd = $db->createCommand ( $sql )->bindParam ( ':BusinessInfoID', $userId );
		return $cmd->queryOne ();
	}
	
	/**
	 * 判定商家是否支持一口的支付方式
	 *
	 * @param number $businessUserid        	
	 * @param number $payType        	
	 * @return boolean
	 */
	public static function checkBusinessPayType($businessUserid = 0, $payType = 0) {
		$res = self::getPayMethod ( $businessUserid );
		$payType = ($payType == 5 ? 4 : $payType); // 2016 01 22 微信支付判定标准走余额支付
		if (empty ( $res )) {
			return false;
		}
		if ($res ["IsAllPay"] == "1") {
			return true;
		}
		if ($res ["IsBalancePay"] == "1" && $payType == 4) { // 余额支付
			return true;
		} else if ($res ["IsToPay"] == "1" && $payType == 2) { // 到付
			return true;
		} else if ($res ["IsNowPay"] == "1" && $payType == 1) { // 现付
			return true;
		}
		return false;
	}
	
	/**
	 * 根据货物价值判断是否冻结[根据区域来判断]
	 * 
	 * @param type $goodsCost        	
	 * @return type
	 */
	public static function checkIsPreciousBySphereOfBusiness($goodsCost = 0, $sphereID = 0) {
		$cacheName = "BusinessCheckIsPrecious" . $goodsCost . $sphereID;
		$cacheValue = CommonFun::getCache ( $cacheName );
		if (! empty ( $cacheValue )) {
			return $cacheValue;
		}
		$sql = "SELECT Count(0) from common_SphereOfBusiness WHERE CreditScore < :WorthPrice AND SphereID = :SphereID LIMIT 1";
		$res = Yii::$app->db->createCommand ( $sql )->bindParam ( ':WorthPrice', $goodsCost )->bindParam ( ':SphereID', $sphereID )->queryColumn ();
		CommonFun::setCache ( $cacheName, $res [0] != 0, 60 );
		return $res [0] != 0;
	}
	
	/**
	 * 获取订单扩展信息
	 * @author RTS 2017年2月16日 14:48:02
	 * @param unknown $manageFreightArray
	 */
	public static function getExtInfo($manageFreightArray = []) {
		if(!isset($manageFreightArray['priceDetail'])){
			return [];
		}
		$manageFreightArray = $manageFreightArray['priceDetail'];
		$res = [
			'start_fee' => 'firstPrice',
			'distance_fee' => 'distancePrice',
			'order_base_fee' => 'basePrice',
			'weight_fee' => 'weightPrice',
			'worth_fee' => 'goodsWorthPrice',
			'transport_fee' => 'transportPrice',
			'night_fee' => 'nightPrice',
			'weather_fee' => 'weatherPrice',
			'cross_river_fee' => 'crossRiverPrice',
			'business_discount_fee' => 'businessDiscount',
			'prefer_fee' => 'prefermoney',
		    //'ontime_service_fee' => 'onTimeServicePrice'
		];
		foreach ( $res as $k => $item ) {
			$res [$k] = CommonFun::getArrayValue ( $manageFreightArray, $item, 0 );
		}
		return $res;
	}
	
	
	public static function existForBarCode($bar_code = ''){
		if(empty($bar_code)){
			return '条形码为空。';
		}
		
		if(strlen($bar_code) > 200){
			return '条形码超过最大长度。';
		}
		$res = self::checkExistForBarCode($bar_code);
		if($res){
			return '条形码：'.$bar_code.'已经存在。';
		}
		
		return true;
	}
	
	/**
	 * 检查条形码是否存在
	 * @author rentingshuang <tingshuang@rrkd.cn>
	 * @param type $platform_id
	 * @param type $business_no
	 * @param type $main_business_no
	 * @return type
	 */
	public static function checkExistForBarCode($bar_code = '') {
		if(empty($bar_code)){
			return false;
		}
		$codeOnly = isset(Yii::$app->params['NoCheckUser']) && isset(Yii::$app->params['NoCheckUser']['codeOnly']) ? Yii::$app->params['NoCheckUser']['codeOnly'] : 0;
		if($codeOnly == 0){
			return false;
		}
		$sql = "SELECT count(0) from platform_order_bar_code where bar_code  = :bar_code AND is_del = 0; ";
		$res = Yii::$app->db->createCommand($sql)->bindParam(":bar_code", $bar_code)->queryColumn();
		return $res[0] > 0;
	}
	
	/**
	 * 写入barCode 条形码
	 * @param string $bar_code
	 * @param number $goodsId
	 * @return boolean|number
	 */
	public static function addBarCode($bar_code = '',$goodsId = 0) {
		if(empty($bar_code) || empty($goodsId)){
			return false;
		}
		$codeOnly = isset(Yii::$app->params['NoCheckUser']) && isset(Yii::$app->params['NoCheckUser']['codeOnly']) ? Yii::$app->params['NoCheckUser']['codeOnly'] : 0;
		if($codeOnly == 0){
			return false;
		}
		$sql = "INSERT platform_order_bar_code(goodsId,bar_code,ctime) VALUES(:goodsId,:bar_code,now());";
		$res = Yii::$app->db->createCommand($sql)->bindParam(":bar_code", $bar_code)->bindParam(":goodsId", $goodsId)->execute();
		return $res;
	}
	
	/**
	 * 获取角色id
	 * @param number $id
	 * @return Ambigous <boolean, string, \yii\caching\mixed, \yii\caching\Dependency, \yii\caching\false>|number
	 */
	public static function getBusinessSendhelperId($id = 0){
		$cacheName = 'getBusinessSendhelperId' . $id;
		$cacheValue = CommonFun::getCache($cacheName);
		if (!empty($cacheValue)) {
			return $cacheValue;
		}
	
		$sql = "select sendhelperid from BusinessInfo where ID=:ID and BusinessType in (0,3)";
		$cmd = Yii::$app->db->createCommand($sql);
		$cmd->bindParam(':ID', $id);
		$res = $cmd->queryScalar();
		if(empty($res)){
			return 0;
		}
		$return = intval($res);
		CommonFun::setCache($cacheName,$return,60);
		return $return;
	}
	
	
	/**
	 * 获取订单信息
	 * @param number $orderId
	 * @param number $ordertype
	 */
	public static function getOrderInfo($orderId = 0,$ordertype = 1){
		$sql = "SELECT CourierId,ClaimPickupDate,PacksID FROM fast_GoodsInfo WHERE GoodsId = :id AND Deled = 0 LIMIT 1;";
		if($ordertype == 2){ 
			$sql = "SELECT CourierId,null as ClaimPickupDate,0 as PacksID FROM DG_AgentBuy WHERE BuyId = :id AND Deled = 0 LIMIT 1;";
		}

		$cmd = Yii::$app->db->createCommand($sql);
		$cmd->bindParam(':id', $orderId);
		return $cmd->queryOne();
	}
	
	/**
	 * 拼单信息
	 * @param number $packsId
	 * @return Ambigous <multitype:, \yii\db\false, \yii\db\mixed, mixed>
	 */
	public static function getPacksInfo($packsId = 0){
		$sql = "SELECT PacksNumber FROM fast_GoodsInfo_Packs WHERE PacksID = :id LIMIT 1;";
		$cmd = Yii::$app->db->createCommand($sql);
		$cmd->bindParam(':id', $packsId);
		return $cmd->queryOne();
	}
	
	/**
	 * 获取订单处罚信息
	 * @author RTS 2017年3月14日 16:06:11
	 * @param number $orderId
	 * @param number $ordertype 订单类型 1 普通帮送 
	 */
	public static function getOrderPunishInfo($orderId = 1,$ordertype = 1){
		$return = [
			'receiveTime' => 0,//接单时间
			'punishMoney' => 0,//处罚金额
			'disclaimerTimes' => 0,//免责次数
			'punishStatus' => 0,//0 不处罚 即不显示处罚信息  1 处罚 显示处罚信息 但有可能是免责的 
			'orderStatus' => 0,//订单状态 0待接单 或1已接单
			'isClaimPickup' => 0,//是否预约
			'isPacks' => 0,//是否是拼单 这里只取非自拼的
			'packTxt' => '',
		];	

		if(empty($orderId)){
			return $return;
		}
		$info = self::getOrderInfo($orderId,$ordertype);
		if(!empty($info)){
			$return['orderStatus'] = empty($info['CourierId']) ? 0 : 1;
			//$return['isClaimPickup'] = empty($info['ClaimPickupDate']) ? 0 : 1;
			if(!empty($info['PacksID'])){
				$info = self::getPacksInfo($info['PacksID']);
				if(!empty($info)){
					$packsNumber = $info['PacksNumber'];
					$isPacks = CommonFun::startWith($packsNumber,'sp',true);//非自动拼单即视为手动拼单
					$return['isPacks'] = $isPacks ? 0 : 1;//只取手动拼单
					$return['packTxt'] = $return['isPacks'] ? '取消拼单中的子订单有可能受到处罚' : '';
				}
			}
		}

		/*
	    $return['orderStatus'] = 1;
		$return['isPacks'] = 1;
		$return['packTxt'] = $return['isPacks'] ? '取消拼单中的子订单有可能受到处罚。' : '';
		*/
		$data = [
			'ordertype' => $ordertype,
			'goodsid' => $orderId,
			'reqName' => 'getpunish',
		];
		
		
		$url = Yii::$app->params['RRKDInterfaceHost'] . '/RRKDInterface/Interface/fastInterface.php';
		$res = CommonFun::callInterface('getpunish', $data, $url);
		CommonFun::log('调用URL：'.$url.'，结果：'.$res.'，数据：'.json_encode($data,JSON_UNESCAPED_UNICODE),'punishInfo','punishInfo');
		$data = CommonValidate::isJson($res);
		if($data === false || $data['success'] == 'false'){
			return $return;
		}
		
		$return['receiveTime'] = intval(CommonFun::getArrayValue($data,'minutes',0));
		
		$return['isClaimPickup'] = intval(CommonFun::getArrayValue($data,'isclaimpickup','false') == 'true');
		//$return['orderStatus'] = intval(CommonFun::getArrayValue($data,'status',0));
		
		$return['punishStatus'] = intval(CommonFun::getArrayValue($data,'punish_enabled','false') == 'true');
		$return['punishMoney'] = floatval(CommonFun::getArrayValue($data,'deduct_money',0));
		$return['disclaimerTimes'] = intval(CommonFun::getArrayValue($data,'avoid_num',0));
		
		if($return['orderStatus'] == 0 && $return['punishStatus'] == 1){//未接单 返回开启处罚则手动关掉处罚
			$return['punishStatus'] = 0;
		}
		
		/*
		$return['punishStatus'] = 1;
		$return['disclaimerTimes'] = 0;
		$return['isClaimPickup'] = 0;
		$return['receiveTime'] = 10;
		$return['punishMoney'] = 5.55;
		*/
		return $return;
	}
	
	/**
	 * 获取用户配置信息
	 * @param string $userName
	 * @return boolean|Ambigous <boolean, mixed>
	 */
	public static function getUserConfig($userName = ''){
		$def['invoice_url'] = '';
		if(empty($userName)){
			return $def;
		}
		$cacheName = 'getExtInfo' . $userName;
		$cacheValue = CommonFun::getCache($cacheName);
		if (!empty($cacheValue)) {
			return $cacheValue;
		}
		
		$url = Yii::$app->params ['RRKDInterfaceHost'] . '/RRKDInterface/Interface/loginInterface.php';
		$data = [];
		$data["reqName"] = "userConfig";
		$res = CommonFun::callInterface($data["reqName"], $data,$url,'RrkdHttpTools','',$userName);
		$res = CommonValidate::isJson($res);
		if($res === false || $res['success'] != 'true'){
			return $def;
		}
		CommonFun::setCache($cacheName,$res,300);
		return $res;
	}
	
	/**
	 * 生成token
	 * @param unknown $userName
	 * @param unknown $pwd
	 * @return string
	 */
	public static function getToken($userName, $pwd) {
		return strtoupper ( md5 ( strtoupper ( md5 ( $userName ) ) . strtoupper ( $pwd ) ) );
	}
	
	/**
	 * 快递员今日是否购买保险
	 * @param number $userId
	 * @return boolean
	 */
	public static function todayCourierIsInsuranced($userId = 0){
		//return true;
		$url = 'http://interface.rrkd.cn/insurance/default/userInsuranceInfo';
		$data = [
			'userID' => $userId
		];
		
		$res = CommonFun::curlPost($url,$data);
		CommonFun::log('userId：'.$userId.'结果：'.$res,__FUNCTION__,'CommonBusiness');
		$res = CommonValidate::isJson($res);
		if($res === false || $res['status'] != 200 || !isset($res['data']['insuInfo'])){
			return true;
		}
		
		$insuInfo = $res['data']['insuInfo'];
		if($insuInfo['needInsurance'] == 0){
			return true;
		}
		
		return $insuInfo['todayIsInsuranceed'] == 1;
	}
	
	/**
	 * 快递员派单
	 * @param number $goodsId 订单号
	 * @param number $barcode 条形码
	 * @param string $userName 快递员手机号
	 * @param string $lat 快递员经度
	 * @param string $lon 快递员纬度
	 * @return string|Ambigous <>|boolean 
	 */
	public static function receiveOrder($data = [],$isUpdateLdp = true) {
		$goodsId = CommonFun::getArrayValue($data,'goodsId',0);
		$barcode = CommonFun::getArrayValue($data,'barcode',0);
		$userId = CommonFun::getArrayValue($data,'userId',0);
		$lat = CommonFun::getArrayValue($data,'lat',0);
		$lon = CommonFun::getArrayValue($data,'lon',0);
		
		$isNeedPickUp = 0;//是否需要取货过程
		if(!$isUpdateLdp){
			$goodsInfo = CommonNewSellOrder::findOne(['goodsId' => $goodsId]);
			$isNeedPickUp = intval($goodsInfo['type'] == 1);
			if($goodsInfo && $goodsInfo['type'] != 1){//非干线订单提示保险信息
				$insuranced = self::todayCourierIsInsuranced($userId);
				if(!$insuranced){
					return [
						'status' => 0,
						'msg' => '指定失败：该自由人今日未购买保险，请提示自由人购买。',
					];
				}
			}
		}

		$data = [];
		$data["reqName"] = 'vicinageGrabPlantformGoods';
		$data["isSiteVicinage"] = 1;
		$data["isNeedPickUp"] = $isNeedPickUp;
		$data["goodsid"] = $goodsId;
		$data["barcode"] = $barcode;
		$data["paytype"] = 1;
		
		$data["lat"] = $lat;
		$data["lon"] = $lon;
		$data["isRetail"] = intval(!$isUpdateLdp); 
		$self = new self();
		$userName = '';
		$token = $self->getUserToken($userId,$userName);
	
		$url = Yii::$app->params['FreeManInterfaceHost'] . '/RRKDInterface/Interface/courierInterface.php';
		$res = CommonFun::callInterface('vicinageGrabPlantformGoods', $data, $url,'RrkdHttpTools',$token,$userName);
		CommonFun::log('URL:'.$url.'，token:'.$token.'，结果：'.$res.',$data:'.json_encode($data,JSON_UNESCAPED_UNICODE),'receiveOrder','receiveOrder');
		$res = CommonValidate::isJson($res);
		
		$msg = '';
		$status = 0;
		if($res == false){
			$msg = '接口响应不正确。';
		}elseif($res['success'] != "true" ){
			$msg = $res['msg'];
		}
		$status = empty($msg) ? 1 : 0;
		if($isUpdateLdp){
			$self->updateOrderAssign($status,$msg,$goodsId);
		}
		
		return [
			'status' => $status,
			'msg' => $msg,
		];
	}
	
	/**
	 * 获取用户tokend
	 * @param number $userId
	 * @return string
	 */
	public function getUserToken($userId = 0,&$userName = ''){
		$cacheName = 'yf_getUserToken_xx'.$userId;
		$cacheValue = CommonFun::getCache($cacheName);
		if(!empty($cacheValue) && is_array($cacheValue)){
			$userName = $cacheValue['uname']; 
			return $cacheValue['token'];
		}
		
		$db = Yii::$app->db;
		$sql = "select UserName,`Password` from common_Login where UserID = :userId and Deled = 0 LIMIT 1";
		$cmd = $db->createCommand($sql);
		$cmd->bindParam(':userId', $userId);
		$res = $cmd->queryOne();
		$token = self::getToken($res['UserName'],$res['Password']);
		$tmp = [
			'uid' => $userId,
			'token' => $token,
			'uname' => $res['UserName'],
		];
		$userName = $tmp['uname'];
		CommonFun::setCache($cacheName,$tmp,3600);
		return $token;
	}
	
	public function updateOrderAssign($status = 0,$msg = '',$goodsId = 0){
		try {
			$url = "http://wd.rrkd.cn/expressApi/default.html?status={$status}&msg={$msg}&goodsId={$goodsId}";
			$res = CommonFun::curlGet($url,10);
		}catch (\Exception $ex){
			$res = 'Exception'.$ex->getMessage();
		}
		CommonFun::log('更新数据：URL:'.$url.'，结果：'.$res,'receiveOrder','receiveOrder');
		return true;
	}
	
	/**
	 * 是否是人人快递订单号
	 * @param string $goodsNumber
	 */
	public static function isOrderNumber($goodsNumber = '') {
		if(empty($goodsNumber)){
			return false;
		}
		return CommonFun::startWith($goodsNumber,'15') && strlen($goodsNumber) == 16;
	}
	
	/**
	 * 生成订单号
	 * @param number $numbers
	 * @param number $len
	 * @param string $prefix
	 */
	public static function createOrderNumber($numbers = 1,$len = 14,$prefix = ''){
		$res = [];
		for($i = 0;$i < $numbers;$i++){
			$tmp = CommonFun::getOrderNumber();
			$start = strlen($tmp)-$len;
			$start = $start < 0 ? 0 : $start;
			$res[] = $prefix.substr($tmp, $start);	
		}
		return $res;
	}

	/**
	 * 发单成功
	 * @param number $orderId 订单id 
	 * @param number $orderType 1 快件   4 拼单
	 */
	public static function sendSuccess($orderId = 0, $orderType = 1){
		$url = Yii::$app->params ['RRKDInterfaceHost'] . '/RRKDInterface/Interface/fastInterface.php';
		$data ["reqName"] = "sendOrderAfter";
		$data ["orderid"] = $orderId;
		$data ["order_type"] = $orderType;

		$res = CommonFun::callInterface ( $data ["reqName"], $data, $url );
		$data = json_encode ( $data, JSON_UNESCAPED_UNICODE );
		
		CommonFun::log ( "发单成功调用地址：{$url}，传入参数：{$orderId}，推送参数：{$data},推送结果：{$res}", 'sendSuccess', 'sendSuccess' );
		return $res;
	}

	
	/**
	 * 充值成功 处理
	 * @param number $bussinessId 商户id
	 * @param number $transactionDetailsId 交易流水id
	 * @param number $amount 金额
	 */
	public static function rechargeSuccess($bussinessId = 0,$transactionDetailsId = 0,$amount = 0){
		CommonFun::log('参数：'.json_encode(func_get_args(),JSON_UNESCAPED_UNICODE) ,__FUNCTION__,__FUNCTION__);
		$res = self::getBusinessInfo($bussinessId);
		if($res){
			try {
				$cityInfo = CommonExtBCityCode::findOne(['Value' => $res['City'],'LeveID' => 2]);
				$model = new CommonBusinessStatisticsDetails();
				$model->city = $res['City'];
				$model->city_code = $cityInfo['Code'];
				$model->bussiness_name = $res['BusinessName'];
				$model->bussiness_id = $bussinessId;
				$model->object_id = $transactionDetailsId;
				$model->type = 1;
				$model->amount = $amount;
				$model->insert_date = date('Y-m-d H:i:s');
				$tmp = $model->save();
				CommonFun::log('保存结果：'.$tmp ,__FUNCTION__,__FUNCTION__);
			
				$url = Yii::$app->params['OperateSystemHost'].'/open/businessDiscount/charge';
				$data ['city'] = $res['City'];
				$data ['cityCode'] = $cityInfo['Code'];
				
				$data ['bussinessId'] = $bussinessId;
				$data ['objectId'] = $transactionDetailsId;
				$data ['amount'] = $amount;
				$data ['insertDate'] = $model->insert_date;
					
				$header [] = "Content-Type: application/json";
				$header [] = "Accept: application/json";
				
				$res = CommonFun::curlPost($url,json_encode($data),10,$header);
				$data = json_encode ( $data, JSON_UNESCAPED_UNICODE );
				$resTmp = CommonValidate::isJson($res);
				$desc = "";
				if($resTmp === false){
					$desc = "非正确的响应【无响应或返回的非json数据】";
				}
				
				CommonFun::log ( "调用URL：{$url}，调用参数：{$data},调用结果：{$desc}{$res}", __FUNCTION__,__FUNCTION__);
			}catch (\Exception $ex){
				CommonFun::log('异常：'.$ex->getMessage(),__FUNCTION__,__FUNCTION__);
				return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * 获取商户城市等级
	 * @param string $city
	 * @param string $lon
	 * @param string $lat
	 */
	public static function getDiscountDetail($bussinessId = 0,$city = '',$lon = '',$lat = ''){
		$cityInfo = CommonExtBCityCode::findOne(['Value' => $city,'LeveID' => 2]);
		if(!empty($cityInfo)){
			$url = Yii::$app->params['OperateSystemHost'].'/open/businessDiscount/getDiscountDetail';
			$data ['city'] = $city;
			$data ['cityCode'] = $cityInfo['Code'];
				
			$data ['businessId'] = $bussinessId;
			$data ['lon'] = $lon;
			$data ['lat'] = $lat;

			$header [] = "Content-Type: application/json";
			$header [] = "Accept: application/json";
			$res = CommonFun::curlPost($url,json_encode($data),10,$header);
			$data = json_encode ( $data, JSON_UNESCAPED_UNICODE );
			CommonFun::log ( "调用URL：{$url}，调用参数：{$data},调用结果：{$res}", __FUNCTION__,__FUNCTION__);
			$res = CommonValidate::isJson($res);
			if($res === false){
				return [];
			}
			return $res;
		}
		return [];
	}
	
}
