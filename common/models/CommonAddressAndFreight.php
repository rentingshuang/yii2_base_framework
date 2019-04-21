<?php

namespace common\models;

use Yii;
use yii\base\Model;
use common\components\CommonFun;
use common\components\CommonValidate;


class CommonAddressAndFreight extends Model {
	
   /**
    * 很复杂的计算运费 TODO 这个文件需要规范大小写 时间充裕了做
    * @param xx
    * @author RTS 2017年2月21日 17:51:33
    */
    public static function orderFreightCalculation($serviceFees = 0,$UserID = 0, $SendProvince ='', $SendCity = '', $SendCounty ='', $SendAddress = '', $ReceiveProvince ='', $ReceiveCity = '', $ReceiveCounty ='', $ReceiveAddress ='', $GoodsWeight = 1, $GoodsCost = 1, $Transport = 0, $ClaimPickupDate = '', &$UserFreightArray, &$ManageFreightArray, &$LocationArray, $r_LocationArray, $S_LocationArray,$checkInSphereOfBusiness = 1) {
    	$UserFreightArray = null;
        $ManageFreightArray = null;
        $LocationArray = null;
        if ($GoodsCost > 5000) {
            return '不能发布价值大于5000的货物';
        }
        $SendLocation = $ReceiveLocation = null;
        $SendAddressMsg = self::checkAddressAndOutLocation($SendProvince, $SendCity, $SendCounty, $SendAddress, $SendLocation, "发货地", null);

        if (!empty($SendAddressMsg)) {
            return $SendAddressMsg;
        }
        
        if ($S_LocationArray != null) {
            $SendLocation["lon"] = $S_LocationArray["lon"];
            $SendLocation["lat"] = $S_LocationArray["lat"];
        } else {
            //寄件地经纬度直接读取商家表信息
            $businessModel = CommonBusiness::getBusinessInfo($UserID);
            if (!$businessModel) {
                return "商家信息未能获取！";
            }
            $SendLocation["lon"] = $businessModel['Longitude'];
            $SendLocation["lat"] = $businessModel['Latitude'];
        }

        $ReceiveAddressMsg = self::checkAddressAndOutLocation($ReceiveProvince, $ReceiveCity, $ReceiveCounty, $ReceiveAddress, $ReceiveLocation, "收货地", $r_LocationArray);
        if (!empty($ReceiveAddressMsg)) {
            return $ReceiveAddressMsg;
        }
        if ($r_LocationArray != null) {
            $ReceiveLocation["lon"] = $r_LocationArray["lon"];
            $ReceiveLocation["lat"] = $r_LocationArray["lat"];
        }
     
        $LocationArray["ReceiveLon"] = $ReceiveLocation["lon"];
        $LocationArray["ReceiveLat"] = $ReceiveLocation["lat"];
        
        $SphereID = $ShippingPriceID = 0;
        $nocheck = !$checkInSphereOfBusiness;
        if(!$nocheck){
	        $SphereOfBusinessMsg = self::checkAddressInSphereOfBusiness($SendLocation, $ReceiveLocation, $SphereID, $ShippingPriceID,$SendAddress,$ReceiveAddress);
	        if (!empty($SphereOfBusinessMsg) ) {
	            return $SphereOfBusinessMsg;
	        }
        }
        if (!empty($ClaimPickupDate)) {
            if (!CommonValidate::isDate($ClaimPickupDate)) {
                return "预约取件时间{$ClaimPickupDate}格式错误！";
            } else if (strtotime($ClaimPickupDate) - time() < 3600) {//预约时间-当前时间必须大于3600秒 即1小时
                return "预约取件时间{$ClaimPickupDate}必须大于当前时间1小时";
            } else if (strtotime($ClaimPickupDate) - time() > 3600 * 24 * 3) {//预约时间-当前时间必须小于3600*24*秒 即3天内
                return "预约取件时间{$ClaimPickupDate}不能发布3天后的预约件";
            }
        }
        //发件地信息
        $SendLon = $SendLocation["lon"];
        $SendLat = $SendLocation["lat"];
        //收货地信息
        $ReceiveLon = $ReceiveLocation["lon"];
        $ReceiveLat = $ReceiveLocation ["lat"];
		$Pattern = $Transport == 7 ? 0 : 1;//0 步行 其他为驾车
        $Distance = CommonLocationService::LocationToDistance($SendLon, $SendLat, $ReceiveLon, $ReceiveLat,1,$Pattern);

        $DistanceData = $Distance;
        if ($Distance == -1) {
            return "配送距离计算失败，请联系客服";
        } else {
            $Distance = $Distance * 1000;
        }
   
        //调用接口定价
        $ManageFreightArray = self::getFreightNew($UserID, $ReceiveProvince, $ReceiveCity, $ReceiveAddress, $ReceiveLat, $ReceiveLon, $SendProvince, $SendCity, "", $SendAddress, "", $SendLat, $SendLon, $GoodsWeight, $GoodsCost, $Distance, $Transport, $ClaimPickupDate, "",$serviceFees);
        if (empty($ManageFreightArray) || $ManageFreightArray["success"] == 'false') {
        	$msg = isset($ManageFreightArray['msg']) && !empty($ManageFreightArray['msg']) ? $ManageFreightArray['msg'] : '结算价错误，请稍候再试。';
        	CommonFun::log($msg,'selCostFalse','selCostFalse');
            return $msg;
        }
        if($nocheck){
        	//$info = Yii::$app->params['NoCheckUser'];
        	//$ManageFreightArray['timetext'] = $info['timeText'];
        	//$ManageFreightArray['timevalue'] = $info['timeValue'];
        }
        
        $LocationArray = [];
        $LocationArray["DistanceData"] = $Distance;
        $LocationArray["SendLon"] = $SendLon;
        $LocationArray["SendLat"] = $SendLat;
        $LocationArray["ReceiveLon"] = $ReceiveLon;
        $LocationArray["ReceiveLat"] = $ReceiveLat;
        $LocationArray["Distance"] = $DistanceData;
        $LocationArray["SphereID"] = $SphereID;
        return '';
    }

    /**
     * 调用接口计算价格
     * @param type $busid
     * @param type $ReceiveProvince
     * @param type $ReceiveCity
     * @param type $ReceiveAddress
     * @param type $Receivelat
     * @param type $Receivelon
     * @param type $SendProvince
     * @param type $SendCity
     * @param type $SendCountry
     * @param type $SendAddress
     * @param type $GoodsType
     * @param type $SendLat
     * @param type $SendLon
     * @param type $GoodsWeight
     * @param type $GoodsCost
     * @param type $Distance
     * @param type $Transport
     * @param type $ClaimPickupDate
     * @param type $packs
     * @return type
     */
    public static function getFreightNew($busid, $ReceiveProvince, $ReceiveCity, $ReceiveAddress, $Receivelat, $Receivelon, $SendProvince, $SendCity, $SendCountry, $SendAddress, $GoodsType, $SendLat, $SendLon, $GoodsWeight, $GoodsCost, $Distance, $Transport, $ClaimPickupDate, $packs,$serviceFees = 0) {
        $data["reqName"] = "selcost";
        $data["goodscost"] = $GoodsCost;
        $data["goodsweight"] = $GoodsWeight;
        $data["goodstype"] = $GoodsType;
        $data["sendlat"] = $SendLat;
        $data["sendlon"] = $SendLon;
        $data["pdatype"] = "7";
        $data["sendprovince"] = $SendProvince;
        $data["sendcity"] = $SendCity;
        $data["sendcounty"] = $SendCountry;
        $data["sendaddress"] = $SendAddress;
        $data["distance"] = $Distance;
        $data["receivelat"] = $Receivelat;
        $data["receivelon"] = $Receivelon;
        $data["transport"] = $Transport;
        $data["receiveprovince"] = $ReceiveProvince;
        $data["receivecity"] = $ReceiveCity;
        $data["receiveaddress"] = $ReceiveAddress;
        $data["roletype"] = "2";
        $data["businessid"] = $busid;
        $data["pickupdate"] = $ClaimPickupDate;
        $data["servicefees"] = $serviceFees;
        if ($packs == "1") {
            $data["packs"] = "1";  //拼单算运费
        }
        $cacheName = "BusinessGetFreightNew" . md5(json_encode($data));
        $cacheValue = CommonFun::getCache($cacheName);
        if(!empty($cacheValue)){
        	return $cacheValue;
        }
        
        $url = Yii::$app->params['RRKDInterfaceHost'] . '/RRKDInterface/Interface/fastInterface.php';
        $res = CommonFun::callInterface('selcost', $data, $url);
        $res = CommonValidate::isJson($res);
        if($res === false){
        	return [];
        }
        CommonFun::setCache($cacheName,$res,60);
        return $res;
    }

    /**
     * 判断两个地址是否在同一个商圈,验证通过返回商圈ID和价格标准
     * @param type $SendLocation
     * @param type $ReceiveLocation
     * @param type $SphereID
     * @param type $ShippingPriceID
     */
    public static function checkAddressInSphereOfBusiness($SendLocation, $ReceiveLocation, &$SphereID, &$ShippingPriceID,$SendAddress='',$ReceiveAddress = '') {
        $SphereID = -1;
        $ShippingPr= -1;
        
        //发件地信息
        $SendProvinceCode = $SendLocation["ProvinceCode"];
        $SendCityCode = $SendLocation["CityCode"];
        $SendCountyCode = $SendLocation["CountyCode"];
        $SendProvince = $SendLocation["Province"];
        $SendCity = $SendLocation["City"];
        $SendCounty = $SendLocation["County"];
        $SendLon = $SendLocation["lon"];
        $SendLat = $SendLocation["lat"];
        //收货地信息
        $ReceiveProvinceCode = $ReceiveLocation["ProvinceCode"];
        $ReceiveCityCode = $ReceiveLocation["CityCode"];
        $ReceiveCountyCode = $ReceiveLocation["CountyCode"];
        $ReceiveProvince = $ReceiveLocation["Province"];
        $ReceiveCity = $ReceiveLocation["City"];
        $ReceiveCounty = $ReceiveLocation["County"];
        $ReceiveLon = $ReceiveLocation["lon"];
        $ReceiveLat = $ReceiveLocation["lat"];

        $SendSphereOfBusiness = self::GetSphereOfBusiness($SendProvinceCode, $SendCityCode, $SendCountyCode);
        if (empty($SendSphereOfBusiness)) {
            return "发件地：[{$SendProvince}{$SendCity}{$SendCounty}]未开通服务，请重新选择。";
        }
        $SendSphereOfBusiness = $SendSphereOfBusiness[0];

        //因2015年3月19日 更改需求，收件地可以为空，可能返回多个结果集
        $ReceiveSphereOfBusiness = self::GetSphereOfBusiness($ReceiveProvinceCode, $ReceiveCityCode, $ReceiveCountyCode);
        if (empty($ReceiveSphereOfBusiness)) {
            return "收件地[".$ReceiveProvince.$ReceiveCity.$ReceiveCounty."]未开通服务，请重新选择。";
        }

        $ReceivePolyline = null;
        $ReceiveSphereID = 0;

        foreach ($ReceiveSphereOfBusiness as $value) {
            $ReceiveSphereID = $value['SphereID'];
            $ReceivePolyline = $value['Polyline'];
            $ReceivePolyLineArray = self::convetToPolylineByDictionary($ReceivePolyline);
            if (self::RRKDIsPointInPolygon($ReceiveLon, $ReceiveLat, $ReceivePolyLineArray)) {
                break;
            }
        }
        if ($ReceivePolyline == null || $ReceiveSphereID == 0) {
            return "收件地[".$ReceiveProvince.$ReceiveCity.$ReceiveCounty."]未开通服务，请重新选择。";
        }

        $SendSphereID = $SendSphereOfBusiness['SphereID'];
        if ($SendSphereID != $ReceiveSphereID) {
            return "发件地：[{$SendProvince}{$SendCity}{$SendCounty}]与收货地:[".$ReceiveProvince.$ReceiveCity.$ReceiveCounty."]之间未开通服务，请重新选择。";
        }

        $SendPolyline = $SendSphereOfBusiness["Polyline"];
        if (empty($SendPolyline) || empty($ReceivePolyline)) {
            return "发件地：[{$SendProvince}{$SendCity}{$SendCounty}]与收货地:[".$ReceiveProvince.$ReceiveCity.$ReceiveCounty."]之间未开通服务，请重新选择。";
        }

        $SendPolyLineArray = self::convetToPolylineByDictionary($SendPolyline);

        if (self::RRKDIsPointInPolygon($SendLon, $SendLat, $SendPolyLineArray)) {
            $ReceivePolyLineArray = self::convetToPolylineByDictionary($ReceivePolyline);
            if (!self::RRKDIsPointInPolygon($ReceiveLon, $ReceiveLat, $ReceivePolyLineArray)) {
                return '收件地：'.$ReceiveAddress.'(经纬度：'.$ReceiveLon.'，'.$ReceiveLat.')未开通服务，请重新选择。';
            }
        } else {
            return '发件地：'.$SendAddress.'(经纬度：'.$SendLon.'，'.$SendLat.')未开通服务，请重新选择。';
        }
        $SphereID = $SendSphereID;
        $ShippingPriceID = $SendSphereOfBusiness['ShippingPriceID'];
        return '';
    }
    
    /**
     * 通过省市区code获取商圈经纬度配置
     * @param type $ProvinceCode
     * @param type $CityCode
     * @param type $AreaCode
     * @return type
     */
    public static function GetSphereOfBusiness($ProvinceCode, $CityCode, $AreaCode = '') {
    	$sql = "select SphereID,Polyline,AreaCode,ShippingPriceID from common_SphereOfBusiness
            where ProvinceCode=:ProvinceCode and CityCode = :CityCode and Deleted = 0 and IsEnable = 1";
    	if (!empty($AreaCode)) {
    		$sql = "select SphereID,Polyline,AreaCode,ShippingPriceID from common_SphereOfBusiness
            where ProvinceCode=:ProvinceCode and CityCode = :CityCode and AreaCode like '%" . $AreaCode . "%'   and Deleted = 0 and IsEnable = 1 limit 1";
    	}
    	$cmd = Yii::$app->db->createCommand($sql);
    	$cmd->bindParam(':ProvinceCode', $ProvinceCode);
    	$cmd->bindParam(':CityCode', $CityCode);
    	$res = $cmd->queryAll();
    	
    	return $res;
    }

    /**
     * 计算点是否在圈内（核心区域）
     *
     * @param 经度 $ALon        	
     * @param 纬度 $ALat        	
     * @param 核心区域坐标集合 $Points        	
     */
    public static function RRKDIsPointInPolygon($ALon, $ALat, $Points) {
        $boundOrVertex = true;
        $intersectCount = 0;
        $N = count($Points);
        $precision = 0.0000000002;
        // 将纬度放到数组
        $P = array(
            $ALon,
            $ALat
        );

        $PointsA = $Points [0];
        $PointsB = array();

        for ($i = 1; $i <= $N; $i ++) {
            // 相等直接返回
            $var = array_diff($P, $PointsA);
            if (empty($var)) {
                return $boundOrVertex;
            }

            $PointsB = $Points [$i % $N];

            if ($P [1] < min($PointsA [1], $PointsB [1]) || $P [1] > max($PointsA [1], $PointsB [1])) {
                $PointsA = $PointsB;
                continue;
            }

            if ($P [1] > min($PointsA [1], $PointsB [1]) && $P [1] < max($PointsA [1], $PointsB [1])) {
                if ($P [0] <= max($PointsA [0], $PointsB [0])) {
                    if ($PointsA [1] == $PointsB [1] && $P [0] >= min($PointsA [0], $PointsB [0]))
                        return $boundOrVertex;
                    if ($PointsA [0] == $PointsB [0]) {
                        if ($PointsA [0] == $P [0])
                            return $boundOrVertex;
                        else
                            ++$intersectCount;
                    } else {
                        $xinters = ($P [1] - $PointsA [1]) * ($PointsB [0] - $PointsA [0]) / ($PointsB [1] - $PointsA [1]) + $PointsA [0];
                        if (abs($P [0] - $xinters) < $precision) {
                            return $boundOrVertex;
                        }

                        if ($P [0] < $xinters) {
                            ++$intersectCount;
                        }
                    }
                }
            } else {
                if ($P [1] == $PointsB [1] && $P [0] <= $PointsB [0]) {
                    $PointsC = $Points [($i + 1) % $N];
                    if ($P [1] >= min($PointsA [1], $PointsC [1]) && $P [1] <= max($PointsA [1], $PointsC [1])) {
                        ++$intersectCount;
                    } else {
                        $intersectCount += 2;
                    }
                }
            }
            $PointsA = $PointsB;
        }

        if ($intersectCount % 2 == 0) {
            // 偶数在多边形外
            return false;
        } else {
            // 奇数在多边形内
            return true;
        }
    }

    public static function convetToPolylineByDictionary($Points, $Len = 1) {
        $b = preg_split("/[|]/", substr($Points, 0, strlen($Points) - $Len));
        $c = array();
        $i = 0;
        foreach ($b as $row) {
            $c [$i] = preg_split("/[,]/", $row);
            $i ++;
        }
        return $c;
    }

    public static function checkAddressAndOutLocation($Province, $City, $County, $Address, &$Location, $AddressType, $r_LocationArray) {
        $Location = [];
        $AddressCode = CommonExtBCityCode::getAddressCode($Province, $City, $County);
        if (empty($AddressCode)) {
            return $AddressType . "地址[".$Province.$City.$County."]不存在，请检测省市区是否正确!";
        }
        $ProvinceCode = $AddressCode["ProvinceCode"];
        $CityCode = $AddressCode["CityCode"];
        $CountyCode = isset($AddressCode["CountyCode"]) ? $AddressCode["CountyCode"] : 0;
        if (empty($ProvinceCode)) {
            return $AddressType . "省[".$Province."]不存在";
        }
        if (empty($CityCode)) {
            return $AddressType . "市[".$City."]不存在";
        }

        // 特殊地名转换
        if ($City == "省直辖县级行政区划" || $City == "省直辖县级行政单位") {
            $City = $County;
        }
        if ($AddressType == "收货地" && $r_LocationArray == null) {
            $dicAddress = CommonLocationService::addressToLocation($Province, $City, $County, $Address);
            if ($dicAddress["success"] && ($dicAddress['confidence'] >= 10 || $dicAddress['confidence'] == -1) ) {
                $Location["lon"] = $dicAddress["lon"];
                $Location["lat"] = $dicAddress["lat"];
            } else {
                return "不好意思，您的" . $AddressType . "地址[".$Province. $City. $County. $Address."]不够精确或过于混淆不够清晰，请重新选择。";
            }
        }
        
        $Location["ProvinceCode"] = $ProvinceCode;
        $Location["CityCode"] = $CityCode;
        $Location["CountyCode"] = $CountyCode;
        $Location["Province"] = $Province;
        $Location["City"] = $City;
        $Location["County"] = $County;
        return null;
    }

   
    public static function sort($listPoint, $key) {
        $result = [];
        $sort = [];
        if (!empty($listPoint)) {
            foreach ($listPoint as $k => $item) {
                if ($item['pointNameA']['PointName'] == $key || $item['pointNameB']['PointName'] == $key) {
                    $result[] = $item;
                    $sort[] = $item['pointDistance'];
                }
            }
            array_multisort($sort, constant('SORT_ASC'), $result);
        }
        return $result;
    }

}
