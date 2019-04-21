<?php

namespace common\components;

use Yii;
use yii\helpers\ArrayHelper;


class CommonApiHelper {
	
	/**
	 * 生成api数据行
	 * @param unknown $data
	 * @param $commonCfg 公用配置
	 * @author RTS 2016年12月22日 13:59:12
	 */	
	public static function createApiData($data = [],$level = 0,$commonCfg = [],$parentType = 'object') {
		
		$res = ['html'=>'','json'=>[]];
		if (is_array ( $data )) {
			$html = "";
			$level ++;
			$json = [];
			$data = ArrayHelper::merge($commonCfg, $data );
			if(!empty($data)){
				foreach ( $data as $k => $item ) {
					$html .= self::formatApi ( $k, $item,$level );
					$type = CommonFun::getArrayValue ( $item, 'type', 'int' );
					if($type == 'object' || $type == 'array'){
						$child = CommonFun::getArrayValue ( $item, 'item', [ ] );
						$return = self::createApiData ($child,$level,[],$type);
						$html.= $return['html'];
						if($type == 'object'){
							//echo $parentType;exit;
							$json[$k] = $return['json'];
						}else{
							//echo $parentType;exit;
							$json[$k] = [$return['json']];
						}
					}else {
						$json[$k] = CommonFun::getArrayValue ( $item, 'demoValue', CommonFun::getArrayValue ( $item, 'default', ''));
					}
				}
			}
			/*
			foreach ( $data as $k => $item ) {
				$html .= self::formatApi ( $k, $item,$level );
				$type = CommonFun::getArrayValue ( $item, 'type', 'int' );
				if($type == 'object' || $type == 'array'){
					$child = CommonFun::getArrayValue ( $item, 'item', [ ] );
					$return = self::createApiData ($child,$level);
					$html.= $return['html'];
					$json[$k] = $return['json'];
				}else {
					$json[$k] = CommonFun::getArrayValue ( $item, 'default', '');
				}
			}
			*/
			$res['html'] = $html;
			$res['json'] = $json;
			//CommonFun::p($res);
			return $res;
		}
	}
	
	/**
	 * 组合成html
	 * @param string $filed
	 * @param unknown $dataRow
	 * @return string
	 */
	private static function formatApi($filed = '', $dataRow = [],$level = 0) {
		if (empty ( $filed ) || empty ( $dataRow )) {
			return '';
		}
		$html = "<tr>";
		$html .= "<td class='api-left-{$level}'>".($level>1?'-':'')." {$filed}</td>";
		$html .= "<td>" . CommonFun::getArrayValue ( $dataRow, 'type', 'string' ) . "</td>";
		$html .= "<td>" . (CommonFun::getArrayValue ( $dataRow, 'require', 0 ) == 1 ? 'Y' : 'N') . "</td>";
		$html .= "<td>" . CommonFun::getArrayValue ( $dataRow, 'desc', '-' ) . "</td>";
		
		$remark = [];
		$tmp = CommonFun::getArrayValue ( $dataRow, 'remark', '' );
		if(empty($tmp)){
			$demoValue = CommonFun::getArrayValue ( $dataRow, 'demoValue', '' );
			if(!empty($demoValue)){
				$remark[] = '如：'.$demoValue;
			}
		}else{
			$remark[] = $tmp;
		}

		$tmp = CommonFun::getArrayValue ( $dataRow, 'default', '' );
		if (! empty ( $tmp )) {
			$remark[] = "默认：" . $tmp;
		}
		$tmp = CommonFun::getArrayValue ( $dataRow, 'length', [] );
		if (! empty ( $tmp )) {
			$remark[] = "长度：" . json_encode ( $tmp, JSON_UNESCAPED_UNICODE );
		}
		$tmp = CommonFun::getArrayValue ( $dataRow, 'value', [ ] );
		if (! empty ( $tmp )) {
			$remark[] = "大小：" . json_encode ( $tmp, JSON_UNESCAPED_UNICODE );
		}
		$tmp = CommonFun::getArrayValue ( $dataRow, 'enum', [ ] );
		if (! empty ( $tmp )) {
			$remark[] = "范围：" . json_encode ( $tmp, JSON_UNESCAPED_UNICODE );
		}
		if(empty($remark)){
			$remark[] = '-';
		}
		$html .= "<td>" . implode(',', $remark) . "</td>";
		$html .= "</tr>";
		
		return $html;
	}
	
	
}

?>