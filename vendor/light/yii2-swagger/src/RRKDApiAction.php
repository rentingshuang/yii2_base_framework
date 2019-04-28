<?php

namespace light\swagger;

use yii\base\Action;
use common\components\CommonFun;
use common\components\CommonSpyc;
use common\components\CommonValidate;

class RRKDApiAction extends Action {
	/**
	 *
	 * @var string The rest url configuration.
	 */
	public $path;
	public function run() {
		$this->controller->layout = false;
		$request = \Yii::$app->request;
		
		$clearCache = \Yii::$app->request->get ( 'clearCache', false );
		if ($clearCache !== false) {
			$this->clearCache ();
		}
		
		$isGetUrlApi = false;//是否是外链URL
		if ($request->isPost) {
			if($request->isAjax){
				return $this->sendData ();
			}
		}
		
		$url = $request->get ( 'url', '' );
		if(!empty($url)){
			$isGetUrlApi = true;
			$getUrlApiData = $this->getData($url);
			if (empty ( $getUrlApiData )) {
				$getUrlApiData ['api'] = [ ];
			}
		}

		
		$cfg = $isGetUrlApi ? $getUrlApiData : $this->getYml ( $this->path );
		$controllerID = \Yii::$app->controller->id;
		$actionID = \Yii::$app->controller->action->id;
		
		$baseUrl = CommonFun::url ( [ 
				$controllerID . '/' . $actionID 
		] );
		
		$hostInfo = \Yii::$app->request->hostInfo;
		if (! empty ( $cfg )) {
			$hostInfo = CommonFun::getArrayValue ( $cfg ['api'], 'host', $hostInfo );
		}
		//CommonFun::p($cfg);
		$view = $this->controller->getView ();
		return $view->renderFile ( __DIR__ . '/api.php', [ 
				'cfg' => $cfg,
				'baseUrl' => \Yii::$app->request->hostInfo . $baseUrl,
				'hostInfo' => $hostInfo,
				'demoData' => $this->getDemoData(),
		], $this->controller );
	}
	
	private function getDemoData(){
		$str = '{"api":{"name":"商家API","description":"这里是api的说明，包含注意事项以及接口的签名等规则。","setHeader":1,"host":"http:\/\/127.0.0.18:8080","cache":1,"version":"1.0.0","groups":{"A":"用户模块","B":"支付模块","C":"公用模块"},"commonResponse":{"status":{"require":1,"type":"int","desc":"状态 0 失败  1成功"},"msg":{"require":1,"type":"string","desc":"错误消息"}}},"groups":{"A":{"test\/login":{"name":"登录TEST","desc":"用户登录","method":"post","group":"A","params":{"token":{"desc":"令牌","type":"string","default":12}},"response":{"userInfo":{"desc":"用户信息","type":"object","item":{"userId":{"desc":"ID"},"userName":{"desc":"用户名"},"token":{"desc":"令牌"},"shopLocation":{"desc":"地址信息","type":"object","item":{"province":{"desc":"省"}}}}}}},"user\/default\/login":{"name":"登录","desc":"用户登录","method":"post","group":"A","params":{"userName":{"desc":"用户","type":"string","require":1,"default":"15681130807"},"userPwd":{"desc":"密码","require":1,"type":"string","default":"Lm2300705"}},"response":{"userInfo":{"desc":"用户信息","type":"object","item":{"userId":{"desc":"ID"},"userName":{"desc":"用户名"},"token":{"desc":"令牌"},"shopLocation":{"desc":"地址信息","type":"object","item":{"province":{"desc":"省"}}}}}}}},"C":{"user\/account\/goodscategory":{"name":"获取商品类别","desc":"获取商品类别C03","method":"post","group":"C","response":{"categories":{"desc":"类别数据","type":"object","item":{"classId":{"desc":"类别ID"},"name":{"desc":"类别名字"}}}}}}}}';
		return json_decode($str,1);
	}
	
	
	/**
	 * 处理外联URL获取数据
	 * @author RTS 2016年12月29日 15:41:22
	 */
	private function getData($url = '') {
		if(!CommonValidate::isUrl($url)){
			return [];
		}
		$res = CommonFun::curlGet($url);
		$res = CommonValidate::isJson($res);
		if($res === false){
			return [];
		}
		return $res;
	}
	
	/**
	 * 处理发送数据
	 * @author RTS       
	 */
	private function sendData() {
		$request = \Yii::$app->request;
		$postData = $request->post ( 'postData', [ ] );
		$method = $request->post ( 'method', 1 ); // TODO 预留GET方式的时候组织参数

		$url = $request->post ( 'url', '' );
		if (empty ( $url )) {
			return CommonFun::returnFalse ( '缺少POST地址' );
		}
		$postDecodeData = CommonValidate::isJson ( $postData );
		if (! empty ( $postData ) && $postDecodeData === false) {
			return CommonFun::returnFalse ( '非JSON数据' );
		}
		$header = $this->getHeader ();
		if($method){
			$postRes = CommonFun::curlPost ( $url, json_encode ( $postDecodeData ), 10, $header, false );
		}else{
			$url .= '?'.$this->getStr($postDecodeData);
			$postRes = CommonFun::curlGet( $url, 10, $header, 1 );
		}
		
		CommonFun::log('请求地址:'.$url.'，header：'.json_encode($header,JSON_UNESCAPED_UNICODE).'，参数：'.json_encode ( $postDecodeData ,JSON_UNESCAPED_UNICODE).'，返回：'.$postRes,'sendData','RRKDApiAction');
		
		// CommonFun::p($postRes);
		return CommonFun::returnSuccess ( [ 
				'data' => $postRes,
				'url' => $url,
				'header' => ! empty ( $header ) ? implode ( ';', $header ) : '' 
		] );
	}
	
	private function getStr($data = []){
		if(empty($data)){
			return '';
		}
		$res = [];
		foreach ($data as $k=>$item){
			$res[] = $k.'='.$item;
		}
		return implode('&', $res);
	}
	
	/**
	 * 获取请求header
	 *
	 * @author RTS 2016年12月22日 17:00:29
	 */
	private function getHeader() {
		$header = CommonFun::getArrayValue ( $_COOKIE, 'headers', [ ] );
		if (empty ( $header )) {
			return [ ];
		}
		$header = rtrim ( $header, ';' );
		$temp = explode ( ';', $header );
		if (empty ( $temp )) {
			return [ ];
		}
		return $temp;
	}
	
	/**
	 * 清掉缓存
	 *
	 * @author RTS 2016年12月22日 15:13:55
	 */
	private function clearCache() {
		$cache = \Yii::$app->cache;
		$cacheName = $this->getCacheName ();
		$cache->delete ( $cacheName );
	}
	
	/**
	 * 组合缓存名
	 *
	 * @return string
	 */
	private function getCacheName() {
		return  'APIACTION_'. date('Y-m-d') . '_getYmlApi' ;
	}
	
	/**
	 * 获取yml配置
	 *
	 * @param string $path        	
	 * @return []
	 * @author RTS 2016年12月21日 14:08:47
	 */
	private function getYml($path = '') {
		if (empty ( $path ) || ! is_dir ( $path )) {
			return [ ];
		}
		$ymls = CommonFun::getDir ( $path );
		if (empty ( $ymls )) {
			return [ ];
		}
		$info = $this->getYmlInfo ( $path ); // 接口说明 预留输出参数和输入参数通过别名载入
		$cache = \Yii::$app->cache;
		$cacheName = $this->getCacheName ();
		if ($info && isset ( $info ['api'] )) {
			$isCache = CommonFun::getArrayValue ( $info ['api'], 'cache', 0 );
			if ($isCache) {
				$value = $cache->get ( $cacheName );
				if (! empty ( $value )) {
					echo 'cache';
					return $value;
				}
			}
		}
		$groupInfo = isset ( $info ['api'] ) && isset ( $info ['api'] ['groups'] ) ? $info ['api'] ['groups'] : [ ];
		$reGroup = [ ];
		
		try {
			foreach ( $ymls as $modules => $controllers ) {
				if (! is_array ( $controllers ) && is_string ( $controllers )) {
					$file = $path . DIRECTORY_SEPARATOR . $controllers;
					$this->readConfig ( $file, '', $controllers, $reGroup );
				} else {
					foreach ( $controllers as $controller ) {
						$file = $path . DIRECTORY_SEPARATOR . $modules . DIRECTORY_SEPARATOR . $controller;
						$this->readConfig ( $file, $modules, $controller, $reGroup );
					}
				}
			}
		} catch ( \Exception $e ) {
			return [ ];
		}
		
		ksort ( $reGroup );
		$return = array_merge ( $info, [ 
				'groups' => $reGroup 
		] );
		$cache->set ( $cacheName, $return ,24*3600);
		return $return;
	}
	
	/**
	 * 读取具体配置
	 *
	 * @param string $path        	
	 * @param string $modules        	
	 * @param string $controller        	
	 * @param unknown $reGroup        	
	 * @return boolean
	 * @author RTS 2016年12月23日 11:48:14
	 */
	private function readConfig($path = '', $modules = '', $controller = '', &$reGroup = []) {
		$actions = $this->readYml ( $path );
		if (empty ( $actions ) || ! is_array ( $actions )) {
			return true;
		}
		
		foreach ( $actions as $action => $config ) {
			$group = isset ( $config ['group'] ) ? strtoupper ( $config ['group'] ) : (CommonFun::getArrayValue ( $config, 'name', (isset ( $config ['desc'] ) ? $config ['desc'] : '') ));
			$controller = str_replace ( '.yml', '', $controller );
			$url = '';
			if (! empty ( $modules )) {
				$url .= $modules . '/';
			}
			$url .= $controller . '/' . $action;
			if (! isset ( $reGroup [$group] )) {
				$reGroup [$group] [$url] = $config;
			} else {
				$temp = $reGroup [$group];
				$temp [$url] = $config;
				$reGroup [$group] = $temp;
				if (count ( $reGroup [$group] ) > 1) {
					$reGroup [$group] = CommonFun::arrayMultisort($reGroup [$group]);
				}
			}
		}
		return true;
	}
	
	
	
	/**
	 * 读取yml文件内容
	 *
	 * @param string $path        	
	 */
	private function readYml($path = '') {
		if (! file_exists ( $path )) {
			return [ ];
		}
		$return = [ ];
		try {
			$return = CommonSpyc::YAMLLoad ( $path );
		} catch ( \Exception $e ) {
			CommonFun::log ( '读取yml文件失败：' . $path, 'readYml', 'readYml' );
		}
		return $return;
	}
	
	/**
	 * 获取接口说明 和 公共的参数
	 *
	 * @param string $path        	
	 */
	private function getYmlInfo($path = '') {
		$path .= DIRECTORY_SEPARATOR . 'rrkdApi.yml';
		return $this->readYml ( $path );
	}
}
