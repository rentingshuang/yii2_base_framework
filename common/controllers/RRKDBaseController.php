<?php

/**
 * RRKD 接口公用继承类
 * 主要用于接口的常规性校验 【不参与具体业务逻辑】
 * @author RTS 2016年12月23日 09:24:43
 *
 */
namespace common\controllers;

use Yii;
use yii\web\Controller;
use common\components\CommonSpyc;
use common\components\CommonFun;
use common\components\CommonValidate;

class RRKDBaseController extends Controller {
	public $isGet = false;
	public $isPost = false;
	public $isAjax = false;
	public $request = false;
	public $headers = false;
	public function init() {
		$this->request = Yii::$app->request;
		$this->isAjax = $this->request->isAjax;
		$this->isPost = $this->request->isPost;
		$this->isGet = $this->request->isGet;
		$this->headers = $this->request->headers;
		parent::init ();
	}
	
	/**
	 * 接口校验
	 *
	 * @author RTS 2016年9月27日 17:43:12
	 * @param unknown $action动作        	
	 * @param $isCommmonResponse 是否使用公用输出        	
	 * @return boolean true
	 */
	public function check($action = [], $isCommmonResponse = true) {
		if (empty ( $action )) {
			return true;
		}
		$v = $action->id;
		$c = $action->controller->id;
		$m = $action->controller->module->id;
		
		$path = [ ];
		if ($m != \Yii::$app->id) { // 处理没有module 最外层的controller
			$path [] = $m;
		}
		$path [] = $c . '.yml';
		$yml = Yii::getAlias ( '@yml' ) . DIRECTORY_SEPARATOR . implode ( DIRECTORY_SEPARATOR, $path );
		$isCache = CommonFun::getArrayValue ( Yii::$app->params, 'RRKDApiCheckCache', 0 );
		$cache = Yii::$app->cache;
		$cacheName = md5($yml);
		$cacheValue = $cache->get ( $cacheName );
		if ($isCache && !empty($cacheValue)) {
			$config = $cacheValue;
		} else {
			if (! file_exists ( $yml )) {
				return true;
			}
			$config = CommonSpyc::YAMLLoad ( $yml );
		}
		if (empty ( $config ) || ! isset ( $config [$v] )) {
			return true;
		}
		if ($isCache) {
			$cache->set ( $cacheName, $config, 3600 );
		}
		
		$request = Yii::$app->request;
		$params = CommonFun::getArrayValue ( $config [$v], 'params', [ ] );
		if (empty ( $params ) || ! is_array ( $params )) {
			return true;
		}
		
		$request = Yii::$app->request;
		$bodyParams = $request->bodyParams;
		$queryParams = $request->queryParams;
		
		$res = $this->validate ( $params, [ ], '', $request, $bodyParams, $queryParams );
		$request->bodyParams = $bodyParams;
		$request->queryParams = $queryParams;
		
		if ($isCommmonResponse && $res !== true) {
			return CommonFun::returnFalse ( $res );
		}
		return $res;
	}
	
	/**
	 * 校验开始
	 * 
	 * @param unknown $params 配置
	 * @param unknown $dataSource 数据源 递归时候用
	 * @param string $parentFiled 父级字段
	 * @return bool || string
	 * @author RTS 2016年12月23日 14:51:47
	 */
	private function validate($params = [], $dataSource = [], $parentFiled = '', $request, &$bodyParams = [], &$queryParams = []) {
		if (empty ( $params ) || ! is_array ( $params )) {
			return true;
		}
		$def = '';
		foreach ( $params as $filed => $item ) {
			$source = CommonFun::getArrayValue ( $item, 'source', 0 );
			if (! empty ( $dataSource )) {
				$value = CommonFun::getArrayValue ( $dataSource, $filed, $def );
			} else {
				$value = $source == 0 ? $request->post ( $filed, $def ) : $request->get ( $filed, $def );
			}
			
			$require = intval ( CommonFun::getArrayValue ( $item, 'require', 0 ) );
			$desc = CommonFun::getArrayValue ( $item, 'desc', '字段' );
			$filedinfo = $desc;//. '：[' . $filed . ']';
			//CommonFun::cleanXss ( $value ); TODO 考虑XSS在此过滤
			if ($value == $def) {
				$value = $default = CommonFun::getArrayValue ( $item, 'default', '' );
				if ($require && empty ( $value )) {
					return $filedinfo . '不能为空。';
				}
				if ($source == 0) {
					if (! empty ( $dataSource ) && ! empty ( $parentFiled )) { // 递归进来
						if (! empty ( $default )) {
							$str = $parentFiled . '-->' . $filed . '-->' . $default;
							$tmp = $this->oneArray2Marray ( $str );
							$bodyParams = CommonFun::arrayMergeMulti ( $bodyParams, $tmp );
						}
					} else {
						$bodyParams [$filed] = $default;
					}
				} else {
					$queryParams [$filed] = $default;
				}
			}
			
			$type = strtolower ( CommonFun::getArrayValue ( $item, 'type', 'string' ) );
			$type_res = $this->checkType ( $value, $type );
			if (! $type_res && $require) {
				return ($filedinfo . $type . '类型错误。');
			}
			
			$childParams = CommonFun::getArrayValue ( $item, 'item', [ ] );
			if (in_array ( $type, [ 
					'object',
					'array' 
			] ) && ! empty ( $childParams ) && is_array ( $value ) && ! empty ( $value )) {
				$parentTmp = $parentFiled . '-->' . $filed;
				if ($type == 'array') {
					$value = $value [0];
				}
				$dipres = self::validate ( $childParams, $value, $parentTmp, $request, $bodyParams, $queryParams );
				if ($dipres !== true) {
					return $dipres;
				}
			}
			
			$length = CommonFun::getArrayValue ( $item, 'length', [ ] );
			if (! empty ( $length )) {
				$res = $this->checkLength ( $value, $length );
				if ($res !== true) {
					return $filedinfo . $res;
				}
			}
			
			$val = CommonFun::getArrayValue ( $item, 'value', [ ] );
			if (! empty ( $val )) {
				$res = $this->checkValues ( $value, $val );
				if ($res !== true) {
					return $filedinfo . $res;
				}
			}
			
			$enum = CommonFun::getArrayValue ( $item, 'enum', '' );
			if (! empty ( $enum )) {
				$enum = explode ( ',', $enum );
				if (! in_array ( $value, $enum )) {
					return $filedinfo . '错误，不在预期内。';
				}
			}
			
			$callbacks = CommonFun::getArrayValue ( $item, 'validate', [ ] );
			if (! empty ( $callbacks )) {
				$callback = explode ( ',', $callbacks );
				if (count ( $callback ) < 2 || ! method_exists ( $callback [0], $callback [1] )) {
					return ($filedinfo . '自定义方法：' . $callbacks . '不存在。');
				}
				$res = call_user_func ( [ 
						$callback [0],
						$callback [1] 
				], $value );
				if ($res !== true) {
					return ($filedinfo . '自定义方法：' . $callbacks . '返回失败。');
				}
			}
		}
		return true;
	}
	
	/**
	 * 组合数据
	 * 
	 * @param string $str        	
	 * @return [];
	 */
	private function oneArray2Marray($str = '') {
		$str = ltrim ( $str, '-->' );
		$str = explode ( '-->', $str );
		return $this->creatArray ( $str );
	}
	
	/**
	 * 一维数组变多维
	 * 
	 * @param unknown $arr        	
	 * @return multitype: unknown
	 */
	private function creatArray($arr = []) {
		if (empty ( $arr )) {
			return [ ];
		}
		$last = null;
		while ( $last = array_pop ( $arr ) ) {
			if (null != $last) {
				array_push ( $arr, [ 
						array_pop ( $arr ) => $last 
				] );
			}
			if (count ( $arr ) <= 1) {
				break;
			}
		}
		return $arr [0];
	}
	
	/**
	 * 校验类型
	 *
	 * @param string $v        	
	 * @param string $type        	
	 * @author 爽哥哥 2016年9月28日 14:12:58
	 * @return boolean
	 */
	private function checkType($v = '', $type = '') {
		if (empty ( $type )) {
			return false;
		}
		$typeRes = true;
		switch ($type) {
			case 'string' :
				$v = strval ( $v );
				$typeRes = is_string ( $v );
				break;
			case 'int' :
				$v = intval ( $v );
				$typeRes = is_int ( $v );
				break;
			case 'float' :
				$v = floatval ( $v );
				$typeRes = is_float ( $v );
				break;
			case 'date' :
				$typeRes = CommonValidate::isDate ( $v );
				break;
			case 'phone' :
				$typeRes = CommonValidate::isPhone ( $v );
				break;
			case 'mobile' :
				$typeRes = CommonValidate::isMobile ( $v );
				break;
			case 'object' :
				$v = ( object ) ($v);
				$typeRes = is_object ( $v );
				break;
			case 'array' :
				$v = ( array ) $v;
				$typeRes = is_array ( $v );
				break;
			default :
				$v = '';
				$typeRes = false;
		}
		return $typeRes;
	}
	
	/**
	 * 校验长度
	 *
	 * @param string $v        	
	 * @param unknown $length        	
	 * @return string boolean
	 */
	private function checkLength($v = '', $length = []) {
		if (! empty ( $length )) {
			$vlength = strlen ( $v );
			if (isset ( $length ['max'] )) {
				if ($vlength > $length ['max']) {
					return '超过最大长度';
				}
			}
			if (isset ( $length ['min'] )) {
				if ($vlength < $length ['min']) {
					return '小于最小长度';
				}
			}
		}
		return true;
	}
	
	/**
	 * 校验大小
	 * 
	 * @param string $v        	
	 * @param unknown $length        	
	 * @return string boolean
	 */
	private function checkValues($v = '', $length = []) {
		if (! empty ( $length )) {
			if (isset ( $length ['max'] )) {
				if ($v > $length ['max']) {
					return '超过最大值：' . $length ['max'];
				}
			}
			if (isset ( $length ['min'] )) {
				if ($v < $length ['min']) {
					return '小于最小值：' . $length ['min'];
				}
			}
		}
		return true;
	}
}
