<?php

namespace common\models;

use Yii;

use yii\db\ActiveRecord;
use common\components\CommonFun;


/**
 * ContactForm is the model behind the contact form.
 */
class CommonExtBCityCode extends ActiveRecord {
	
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'common_CityCode';
	}
	
    
    /**
     * 获取地区code
     * @param type $Province
     * @param type $City
     * @param type $County
     * @return type
     */
    public static function getAddressCode($Province = '', $City = '', $County = '') {
        $cacheName = "BusinessgetAddressCode" . $Province . $City . $County;
        $cacheValue = CommonFun::getCache($cacheName);
        if (!empty($cacheValue)) {
            return $cacheValue;
        }

        $AddressCode = [];
        $sql = "select a.Code as  ProvinceCode ,b.Code as CityCode  from common_CityCode as a  inner join common_CityCode as b on  a.ID = b.ParentID where a.LeveID  = 1 and a.ParentID = 0  and b.LeveID  = 2 and a.Value = :Province and b.Value  = :City ;";
        if (!empty($County)) {
            $sql = "select a.Code as  ProvinceCode ,b.Code as CityCode ,c.Code as CountyCode from common_CityCode as a inner join common_CityCode as b on  a.ID = b.ParentID inner join common_CityCode as c on  b.ID = c.ParentID  where a.LeveID  = 1 and a.ParentID = 0 and b.LeveID  = 2 AND c.LeveID = 3 and a.Value = :Province and b.Value  = :City and c.Value =:County;";
        }
        $db =Yii::$app->db;
        $cmd = $db->createCommand($sql);
        $cmd->bindParam(':Province', $Province);
        $cmd->bindParam(':City', $City);
        if (!empty($County)) {
            $cmd->bindParam(':County', $County);
        }
        $res = $cmd->queryOne();
        //$db->close();
        if ($res !== FALSE) {
            $AddressCode['ProvinceCode'] = $res['ProvinceCode'];
            $AddressCode['CityCode'] = $res['CityCode'];
            if (!empty($County)) {
                $AddressCode['CountyCode'] = $res['CountyCode'];
            }
            CommonFun::setCache($cacheName, $AddressCode,3600);
        }
        return $AddressCode;
    }

}
