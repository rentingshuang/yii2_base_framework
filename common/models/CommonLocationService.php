<?php

namespace common\models;
use Yii;
use yii\base\Model;
use common\components\CommonFun;
use common\components\CommonValidate;


class CommonLocationService extends Model {
	
	public static $baiduKey = 'IkSvwkWPwCuICyAjnS0QGBzw';
	
	
	/**
	 *  @desc 根据两点间的经纬度计算距离直线
	 *  @param float $lat 纬度值
	 *  @param float $lng 经度值
	 */
	static function getBeelineDistance($lat1, $lng1, $lat2, $lng2)
	{
		$cacheName = 'getDistance'.$lat1.$lng1.$lat2.$lng2;
		$cacheValue = CommonFun::getCache($cacheName);
		if(!empty($cacheValue)){
			return $cacheValue;
		}
		$earthRadius = 6367000; //approximate radius of earth in meters
	
		$lat1 = ($lat1 * pi() ) / 180;
		$lng1 = ($lng1 * pi() ) / 180;
	
		$lat2 = ($lat2 * pi() ) / 180;
		$lng2 = ($lng2 * pi() ) / 180;
	
	
		$calcLongitude = $lng2 - $lng1;
		$calcLatitude = $lat2 - $lat1;
		$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
		$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
		$calculatedDistance = $earthRadius * $stepTwo;
		$res = round($calculatedDistance);
		CommonFun::setCache($cacheName,$res,120);
		return $res;
	}
	
	
    /**
     * 物理地址解析成坐标
     * @param type $Province
     * @param type $City
     * @param type $County
     * @param type $Address
     * @return type
     */
    public static function addressToLocation($Province, $City, $County, $Address) {
        $result['success'] = false;
        $data = [];
        $data["reqName"] = "LocationService";
        $data["type"] = "1";
        $data["province"] = $Province;
        $data["city"] = $City;
        $data["county"] = $County;
        $data["address"] = $Address;
        $url = Yii::$app->params['RRKDInterfaceHost'] . '/RRKDInterface/Interface/ohterInterface.php';
        $res = json_decode(CommonFun::callInterface('LocationService', $data, $url), true);
        if ($res['success'] == "true") {
            $result['success'] = TRUE;
            $result['lon'] = $res['lon'];
            $result['lat'] = $res['lat'];
            $result['confidence'] = $res['confidence'];
            return $result;
        }
        return $result;
    }

    /**
     * 获取两个坐标的距离
     * @param type $Lon
     * @param type $Lat
     * @param type $EndLon
     * @param type $EndLat
     * @param type $Navigation
     * @param type $Pattern
     * @return type
     */
    public static function LocationToDistance($Lon, $Lat, $EndLon, $EndLat, $Navigation = 1, $Pattern = 1) {
        $distance = -1;
        $data["type"] = "2";
        $data["lat"] = $Lat;
        $data["lon"] = $Lon;
        $data["endlat"] = $EndLat;
        $data["endlon"] = $EndLon;
        $data["navigation"] = $Navigation;
        $data["pattern"] = $Pattern;
        $url = Yii::$app->params['RRKDInterfaceHost'] . '/RRKDInterface/Interface/ohterInterface.php';
        $res = json_decode(CommonFun::callInterface('LocationService', $data, $url), true);
        if ($res['success'] == "true") {
            $distance = $res['distance'];
        }
        return $distance;
    }

    /**
     * 通过经纬度反解析详细地址
     * @param type $lon
     * @param type $lat
     * @return null
     */
    public static function getBaiDuLonLatInAddress($lon, $lat) {
        $ak = self::$baiduKey;
        $url = "http://api.map.baidu.com/geocoder/v2/?ak={$ak}&location={$lat},{$lon}&output=json&pois=0";
        $request = CommonFun::curlGet($url);
        if ($request) {
            $request = json_decode($request, TRUE);
            if ($request ["status"] == 0) {
                // Array ( [city] => 成都市 [district] => 青羊区 [province] => 四川省
                // [street] => 少城路 [street_number] => 13号 )
                return $request["result"]["addressComponent"];
            } else {
                return null;
            }
        }
    }
    
	public static function getLonLatFromAddress($city = '', $address = ''){
		$ak = self::$baiduKey;
		$url = "http://api.map.baidu.com/geocoder/v2/?ak={$ak}&output=json&address={$address}&city={$city}";
		$request = CommonFun::curlGet($url);
		if($request){
			$request = CommonValidate::isJson($request);
			return $request ["status"] == 0 ? $request["result"]["location"] : null;
		}else{
			return null;
		}
	}

    /**
     * 获取两个经纬度之间的距离
     * @param $LonA 经度A        	
     * @param $LatA 纬度A        	
     * @param $LonB 经度B        	
     * @param $LatB 纬度B        	
     * @param 是否开启导航 $Navigation      	
     * @param 导航模式1步行2驾车 $Pattern
     * @return string
     */
    public static function getDistance($LonA, $LatA, $LonB, $LatB, $Navigation = true, $Pattern = 1) {
        if ($LonA == $LonB && $LatA == $LatB) {
            return 0;
        }
        $Distance = "";
        $BaiDuLonLatInAddress = self::getBaiDuLonLatInAddress($LonA, $LatA);
        if (!empty($BaiDuLonLatInAddress) && $Navigation) {
            $sLonction = $LatA . ',' . $LonA;
            $eLonction = $LatB . ',' . $LonB;
            $City = $BaiDuLonLatInAddress ["city"];
            $ak = self::$baiduKey;
            $mode = $Pattern == 1 ? "walking" : "driving";
            $url = "http://api.map.baidu.com/direction/v1?mode={$mode}&tactics=12&origin={$sLonction}&destination={$eLonction}&origin_region={$City}&destination_region={$City}&output=json&ak={$ak}";
            // walking 步行
            // driving 驾车
            // http://api.map.baidu.com/direction/v1?mode=driving&tactics=12&origin=30.66500971964,104.0646719566&destination=30.56619599018,104.1473127398&origin_region=成都市&destination_region=成都市&output=json&ak=4032f6db1085b0c63683ef3917e40428
            $request = json_decode(CommonFun::curlGet($url), TRUE);
            if (!empty($request)) {
                if ($request ["status"] == 0) {
                    if ($request ["type"] == 2) {
                        if (isset($request ["result"] ["routes"] [0] ["distance"])) {
                            $Distance = $request ["result"] ["routes"] [0] ["distance"] / 1000;
                        }
                    }
                }
            }
        }
        if ($Distance == 0 || !$Navigation) {
            // 东西经，南北纬处理，只在国内可以不处理(假设都是北半球，南半球只有澳洲具有应用意义)
            $MLonA = $LonA;
            $MLatA = $LatA;
            $MLonB = $LonB;
            $MLatB = $LatB;
            // 地球半径（千米）
            $R = 6371.393;
            $C = Sin(self::Rad($LatA)) * Sin(self::Rad($LatB)) + Cos(self::Rad($LatA)) * Cos(self::Rad($LatB)) * Cos(self::Rad($MLonA - $MLonB));
            $Distance = ($R * Acos($C));
        }
        return $Distance;
    }

    public static function Rad($d) {
        return $d * pi() / 180.0;
    }

}
