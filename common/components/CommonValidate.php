<?php

namespace common\components;

use Yii;

/**
 * 基础验证类
 * @author RTS 2017年2月6日 11:29:40
 *
 */
class CommonValidate {
	
	/**
	 * 请求是否是GET
	 */
	public static function isGet() {
		return Yii::$app->request->isGet;
	}
	
	/**
	 * 请求是否是POST
	 */
	public static function isPost() {
		return Yii::$app->request->isPost;
	}
	
	/**
	 * 是否Ajax
	 */
	public static function isAjax() {
		return Yii::$app->request->isAjax;
	}
	/**
	 * 是否是合法的URL
	 *
	 * @param type $url
	 * @return type
	 */
	public static function isUrl($url) {
		if (empty ( $url )) {
			return false;
		}
		return preg_match ( '/^http[s]?:\/\/' . '(([0-9]{1,3}\.){3}[0-9]{1,3}' . 		// IP形式的URL- 199.194.52.184
				'|' . 		// 允许IP和DOMAIN（域名）
				'([0-9a-z_!~*\'()-]+\.)*' . 		// 域名- www.
				'([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.' . 		// 二级域名
				'[a-z]{2,6})' . 		// first level domain- .com or .museum
				'(:[0-9]{1,4})?' . 		// 端口- :80
				'((\/\?)|' . 		// a slash isn't required if there is no file name
				'(\/[0-9a-zA-Z_!~\'\(\)\[\]\.;\?:@&=\+\$,%#-\/^\*\|]*)?)$/', $url ) == 1;
	}
	
	/**
	 * 是否是电话号码
	 *
	 * @param type $phonenumber        	
	 * @return type
	 */
	public static function isPhone($phonenumber = '') {
		if (strlen ( $phonenumber ) < 7) {
			return false;
		}
		return preg_match ( "/^1\d{10}$|^(0\d{2,3}-?|\(0\d{2,3}\))?[1-9]\d{4,7}(-\d{1,8})?$/", $phonenumber );
	}
	
	/**
	 * 是否是手机号
	 *
	 * @param type $phonenumber        	
	 */
	public static function isMobile($phonenumber = '') {
		return preg_match ( '/1[345789]{1}\d{9}$/', $phonenumber );
	}
	
	
	/**
	 * 密码强度校验
	 * @param string $v
	 * @return boolean
	 */
	public static function isPwd($v = ''){
		if(strlen($v) < 8 || strlen($v) > 15){
			return false;
		}
		return preg_match ( '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $v );
	}
	

	/**
	 * 判断是否为合法的日期
	 *
	 * @param type $str        	
	 * @return type
	 */
	public static function isDate($str = '',$format = 'Y-m-d') {
		return preg_match("/^[0-9]{4}(\-|\/)[0-9]{1,2}(\\1)[0-9]{1,2}(|\s+[0-9]{1,2}(:[0-9]{1,2}){0,2})$/",$str);
		return date ( $format, strtotime ( $str ) ) === $str;
	}
	
	/**
	 * 判断是否字符串是否是JSON
	 *
	 * @param type $string        	
	 * @param type $datas        	
	 * @author RTS 2015年8月3日16:32:23
	 * @return boolean
	 */
	public static function isJson($string, $datas = array()) {
		$datas = json_decode ( $string, true );
		if (json_last_error () == JSON_ERROR_NONE) {
			return $datas;
		}
		return false;
	}
	
}

?>