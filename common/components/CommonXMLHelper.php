<?php

namespace common\components;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * xml 助手类
 *
 * @author 任廷爽 2017年2月6日 20:35:00
 *        
 */
class CommonXMLHelper {
	
	/**
	 * 生成xml文本
	 *
	 * @param array $data        	
	 * @param bool $needHeader 是否加头
	 * 
	 */
	public static function create($data = [], $needHeader = true) {
		if (empty ( $data ) || ! is_array ( $data )) {
			return '';
		}
		$xml = [ ];
		if ($needHeader) {
			$xml [] = '<?xml version="1.0" encoding="UTF-8"?>';
		}
		foreach ( $data as $key => $value ) {
			$temp = is_array ( $value ) ? self::create ( $value, 0 ) : $value;
			$xml [] = "<{$key}>{$temp}</{$key}>";
		}
		return implode ( '', $xml );
	}
	
	/**
	 * 解析xml
	 *
	 * @param unknown $xml        	
	 */
	public static function load($xml = '') {
		if (empty ( $xml )) {
			return [ ];
		}
		$object = @simplexml_load_string ( $xml );
		return self::xmlToArray ( $object );
	}
	
	/**
	 * xml 2 array
	 * @param unknown $xml
	 * @return array
	 */
	public static function xmlToArray($xml) {
		$arr_xml = ( array ) $xml;
		foreach ( $arr_xml as $key => $item ) {
			if (is_object ( $item ) || is_array ( $item )) {
				$arr_xml [$key] = self::xmlToArray ( $item );
			}
		}
		return $arr_xml;
	}
}

?>