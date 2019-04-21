<?php

namespace common\components;

use Yii;
use yii\log\FileTarget;
use yii\mongodb\Query;
use yii\helpers\Url;
use common\models\CommonEmail;
use common\models\CommonHandle;
use common\models\CommonOpenApi;
use common\models\CommonBusiness;
use yii\helpers\ArrayHelper;

class CommonFun {
	
	/**
	 * 检测版本文件
	 * @param string $key 缓存前缀
	 * @param string $path 生成路径
	 */
	static function checkVersion($key = '', $path = '') {
		$redis = Yii::$app->redis;
		$key = 'rrkd-management-' . $key;
		$v = $redis->get ( $key );
		$projectStatus = 1;
		$projectUpdateDesc = '';

		if (empty ( $v )) {
			$projectStatus = isset(Yii::$app->params['projectStatus'])?Yii::$app->params['projectStatus']:1;
			$projectUpdateDesc = isset(Yii::$app->params['projectUpdateDesc']) ? Yii::$app->params['projectUpdateDesc']:'升级中,请稍候再试。';
			if($projectStatus == 0){
				return $projectUpdateDesc;
			}
			return true;
		}
		
		$cres = CommonValidate::isJson($v);
		if($cres === false){
			return '获取升级信息失败，请稍后再试。';
		}
		
	
		$res = self::getArrayValue($cres,'Pconfig',[]);//项目配置
		
		$msg = [];
		$msg[] = "<?php ";
		$msg[] = "return [ ";
		if(!empty($res)){
			$msg[] =" 'upgrade' => ".$res['upgrade'].", // 系统是否需要强制升级
				'systemVersion' => '".$res['ios_version']."',  //系统版本IOS
				'androidSystemVersion'=>".$res['android_version'].", //系统版本安卓
				'apiVersion'=>'".$res['api_version']."', //接口版本号
				'apkDownload'=>'".$res['android_download_url']."', //安卓下地址
				'versionCompatible'=>'".$res['version_compatible']."', //是否兼容
			    'versionUpdateDesc'=>'".$res['update_desc']."', //升级文案	
		   ";
		}
		
		$res = self::getArrayValue($cres,'Project',[]);//项目主配置
		
		
		if(!empty($res)){
			$projectStatus = $res['status'];
			$projectUpdateDesc = $res['update_desc'];
				
			$msg[] = "'projectStatus' => ".$res['status'].", // 系统是否关闭
				    'projectUpdateDesc' => '".$res['update_desc']."',  //关闭原因
			";
		}
		
		$msg[] =" ]; ";
		$msg[] = " ?>";
		
		$msgs = implode('', $msg);
		
		try {
			$log = new FileTarget ();
			$log->logFile = $path;
			$log->messages [] = $msgs;
		
			$log->export (0);
			$redis->set($key,0);
			
		
			if($projectStatus == 0){
				return $projectUpdateDesc;
			}
			return true;
		}catch (\Exception $e){
			return '生成升级文件错误，请稍候再试。';//$e->getMessage();
		}
	}
	
	/**
	 * 对高精度数字的操作
	 * @param number $left 左边数
	 * @param number $right 右边数
	 * @param string $operation 操作 +-*%
	 * @param number $scale 小数位数
	 */
	static function doNumber($left = 0,$right = 0,$operation = '+',$scale = 2,$isRound = false){
		$method = "";
		switch ($operation){
			case '+':
				$method = "bcadd";
				break;
			case '-':
				$method = "bcsub";
				break;
			case '*':
				$method = "bcmul";
				break;
			case '/':
				$method = "bcdiv";
				break;
			case '%':
				$method = "bcmod";
				break;
			case '='://如果两个数相等返回0, 左边的数left_operand比较右边的数right_operand大返回1, 否则返回-1.
				$method = "bccomp";
				break;
		}
		if(empty($method)){
			return false;
		}
		return $method == 'bcmod'? $method($left, $right) : ($method($left, $right, $scale));
	}
	
	/**
	 * 格式化java的时间戳 13位
	 * 
	 * @param string $inputs        	
	 */
	static function formatDate($inputs = '', $format = 'Y-m-d') {
		if (empty ( $inputs )) {
			return '';
		}
		$inputs = substr ( $inputs, 0, 10 );
		return date ( $format, $inputs );
	}
	
	/**
	 * 读取文件夹文件
	 * @param unknown $root
	 * @param string $basePath
	 * @return Ambigous <multitype:, multitype:string >
	 */
	static function getFileList($root, $basePath = '') {
		$files = [ ];
		$handle = opendir ( $root );
		while ( ($path = readdir ( $handle )) !== false ) {
			if ($path === '.git' || $path === '.svn' || $path === '.' || $path === '..') {
				continue;
			}
			$fullPath = "$root/$path";
			$relativePath = $basePath === '' ? $path : "$basePath/$path";
			if (is_dir ( $fullPath )) {
				$files = array_merge ( $files, self::getFileList ( $fullPath, $relativePath ) );
			} else {
				$files [] = $relativePath;
			}
		}
		closedir ( $handle );
		return $files;
	}
	
	/**
	 * 读取指定目录
	 * @param unknown $root
	 * @param string $basePath
	 * @return multitype:string Ambigous <multitype:, multitype:string >
	 */
	static function getDir($root, $basePath = '') {
		$files = [ ];
		$handle = opendir ( $root );
		$exclude  = ['rrkdApi.yml'];
		while ( ($path = readdir ( $handle )) !== false ) {
			if ($path !== '.' && $path !== '..') {
				$fullPath = "$root/$path";
				$relativePath = $basePath === '' ? $path : "$basePath/$path";
				if (is_dir ( $fullPath )) {
					$files [$relativePath] = self::getFileList ( $fullPath );
					// $files[] = $relativePath;
				}else{
					if(!in_array($relativePath,$exclude)){
						$files[] = $relativePath;
					}
				}
			}
		}
		closedir ( $handle );
		return $files;
	}

	
	/**
	 * 获取订单编号
	 *
	 * @author RTS 2016年5月6日 11:34:46
	 */
	public static function getOrderNumber() {
		$url = Yii::$app->params ['OrderNumberServer'];
		$res = self::curlGet ( $url );
		if (! empty ( $res )) {
			return $res;
		}
		return self::creatGoodsNum ();
	}
	/**
	 * 获取快件编号
	 *
	 * @return string
	 */
	public static function creatGoodsNum() {
		$goodsNum = null;
		$date = date ( "Ymd" );
		$round = rand ( 0, 999 );
		$round2 = rand ( 10, 99 );
	
		$cache = Yii::$app->cache;
		$key = $date . "GoodsNum";
		// echo $key,PHP_EOL;
		$num = $cache->get ( $key );
		// echo $num,PHP_EOL;
		if (empty ( $num )) {
			$num = 1000;
		}
		$num ++;
		$cache->set ( $key, $num );
		$goodsNum = substr ( $date, 2 ) . $round . $num . $round2;
		return $goodsNum;
	}
	
	public static function createSql($data = []) {
		if (empty ( $data )) {
			return false;
		}
		$fields = [ ];
		$params = [ ];
		$values = [ ];
		foreach ( $data as $k => $item ) {
			$params [':' . $k] = $item;
			$fields [] = $k;
			$values [] = ':' . $k;
		}
		return [ 
				'fields' => implode ( ',', $fields ),
				'values' => implode ( ',', $values ),
				'params' => $params 
		];
	}

	/**
	 * 获取IP
	 */
	public static function getClientIP() {
		// return Yii::$app->request->userIP;
		$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
		if (empty ( $ip )) {
			$ip = getenv ( "REMOTE_ADDR" );
		}
		return $ip;
	}

	
	/**
	 * 原始cookies
	 *
	 * @param string $name        	
	 * @return unknown string
	 */
	public static function getOriginalCookies($name = '') {
		if (isset ( $_COOKIE [$name] )) {
			return $_COOKIE [$name];
		}
		return '';
	}
	
	/**
	 * cookies操作
	 *
	 * @param unknown $name        	
	 * @param string $value        	
	 * @param string $expire        	
	 * @author RTS 2016年3月3日 17:18:28
	 */
	public static function cookie($name, $value = null, $expire = null, $domain = '') {
		if (false === $value)
			Yii::$app->response->cookies->remove ( $name );
		elseif ($value == null) {
			return Yii::$app->request->cookies->getValue ( $name );
		}
		$expire = $expire ? ($expire * 24 * 3600) : (24 * 3600);
		$expire = time () + $expire;
		$options ['name'] = $name;
		$options ['value'] = $value;
		$options ['expire'] = $expire;
		if (! empty ( $domain )) {
			$options ['domain'] = $domain;
		}
		$cookie = new \yii\web\Cookie ( $options );
		Yii::$app->response->cookies->add ( $cookie );
	}
	
	/**
	 * 获取表单序列化过来的数据
	 *
	 * @return multitype:
	 */
	public static function getSerializeData($name = 'data') {
		$request = Yii::$app->request;
		$data = $request->post ( $name );
		$params = [ ];
		parse_str ( $data, $params );
		return $params;
	}
	
	/**
	 * session操作
	 *
	 * @param unknown $name        	
	 * @param string $value        	
	 * @param string $expire        	
	 * @author RTS 2016年3月3日 17:18:28
	 */
	public static function session($name, $value = null, $expire = null) {
		$session = Yii::$app->session;
		if (false === $value)
			$session->remove ( $name );
		elseif ($value == null) {
			return $session->get ( $name, '' );
		}
		$session->set ( $name, $value );
		if (! empty ( $expire )) {
			$session->setTimeout ( $expire );
		}
	}
	
	/**
	 * 获取CsrfToken
	 * 非常重要
	 */
	public static function getCsrfToken() {
		return Yii::$app->request->getCsrfToken ();
	}
	/**
	 * 删除数据
	 *
	 * @param number $saveDay
	 *        	保留天数
	 * @author RTS 2016年1月28日 14:42:21
	 */
	public static function autoDelMongoData($saveDay = 5) {
		$cache = Yii::$app->cache;
		$cache_name = CommonFun::getCacheName ( "isDelAutoMongoed" );
		if ($cache->exists ( $cache_name ) && ($cache->get ( $cache_name ) == 1)) {
			return true;
		}
		
		$mongo = Yii::$app->mongodb;
		$mc = $mongo->getListCollections ();
		$allCollections = $mc->mongoCollection;
		$startTime = date ( 'Ymd', strtotime ( '-' . $saveDay . ' day' ) );
		foreach ( $allCollections as $collectionName ) {
			$arr = explode ( '_', $collectionName );
			if ($arr [0] < $startTime) {
				$collection = $mongo->getCollection ( $collectionName );
				if ($collection) {
					$collection->drop ();
					self::log ( '自动删除MONGO集合：' . $collectionName, 'autoDelMongoCollections' );
				}
			}
		}
		
		$cache->set ( $cache_name, 1, $saveDay * 3600 * 24 ); // 执行一次有效期管5天 5天后再次执行这个方法才会删
		return true;
	}
	
	/**
	 * 合并多维数据
	 * @return []
	 */
	public static function arrayMergeMulti ()
	{
		$args = func_get_args();
		$array = array();
		foreach ( $args as $arg ) {
			if ( is_array($arg) ) {
				foreach ( $arg as $k => $v ) {
					if ( is_array($v) ) {
						$array[$k] = isset($array[$k]) ? $array[$k] : array();
						$array[$k] = self::arrayMergeMulti($array[$k], $v);
					} else {
						$array[$k] = $v;
					}
				}
			}
		}
		return $array;
	}
	
	/**
	 * 获取数组值
	 * @param unknown $arr
	 * @param unknown $key
	 * @param string $def
	 * @return string|Ambigous <string, unknown>
	 * @author RTS 2016年12月21日 17:48:19
	 */
	public static function getArrayValue($arr = [], $key, $def = '') {
		if (empty ( $arr )) {
			return $def;
		}
		if(!isset($arr [$key])){
			return $def;
		}
		$value = $arr [$key];
		if(is_array($value)){
			if(empty($value)){
				return $def;
			}
		}else{
			if (trim($value) == ""){
				return $def;
			}
		}
		if($value === null){
			return $def;
		}
		return $value;
	}
	
	/**
	 * 加入mongo数据
	 *
	 * @author 爽哥哥 2015年12月18日12:50:15
	 * @param unknown $collection        	
	 * @param unknown $data        	
	 */
	public static function addMongoData($collectionName, $data = []) {
		if (empty ( $data )) {
			return false;
		}
		$mongodb = Yii::$app->mongodb;
		$collection = $mongodb->getCollection ( $collectionName );
		$result = $collection->insert ( $data );
		$mongodb->close ();
		return $result;
	}
	
	/**
	 * 获取mongo数据
	 *
	 * @author 爽哥哥 2015年12月18日12:52:01
	 * @param unknown $collectionName        	
	 * @param unknown $fields        	
	 * @param unknown $where        	
	 * @param number $limit        	
	 * @param string $order        	
	 */
	public static function getMongoData($collectionName, $fields = [], $where = [], $limit = 100, $order = '') {
		$query = new Query ();
		$query->select ( $fields )->from ( $collectionName );
		if (! empty ( $where )) {
			$query->andWhere ( $where );
		}
		$query->limit ( $limit );
		if (! empty ( $order )) {
			$query->orderBy ( $order );
		}
		$rows = $query->all ();
		return $rows;
	}
	
	/**
	 * 唯一串
	 *
	 * @return string
	 */
	public static function getGuid($str = '') {
		$charid = strtoupper ( md5 ( uniqid ( mt_rand (), true ) ) );
		$hyphen = chr ( 45 ); // "-"
		// $uuid = chr ( 123 ) . // "{"
		$uuid = substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 ); // . chr ( 125 ); // "}"
		return md5 ( $str . $uuid );
	}
	
	/**
	 * 清除特殊字符
	 *
	 * @param type $data        	
	 */
	public static function clearSpecialChar(&$data = []) {
		if (isset ( Yii::$app->params ['SpecialFiled'] ) && isset ( Yii::$app->params ['SpecialChar'] )) {
			$fields = Yii::$app->params ['SpecialFiled'];
			$chars = Yii::$app->params ['SpecialChar'];
			foreach ( $fields as $v ) {
				if (isset ( $data [$v] ) && ! empty ( $data [$v] )) {
					if (is_array ( $data [$v] )) {
						foreach ( $data [$v] as $key => $value ) {
							$a = self::clearSpecialChar ( $value, 1 );
							$data [$v] [$key] = $a;
						}
					} else {
						$data [$v] = str_replace ( $chars, array (
								'' 
						), $data [$v] );
					}
				}
			}
			return $data;
		}
	}
	
	/**
	 * 打印
	 *
	 * @author rentingshuang <tingshuang@rrkd.cn>
	 * @param type $msg        	
	 */
	public static function p($msg = '') {
		echo '<pre>';
		print_r ( $msg );
		echo '</pre>';
		exit ();
	}
	
	/**
	 * 生成随机串
	 *
	 * @param number $len        	
	 * @param string $format        	
	 * @return string
	 */
	public static function randStr($len = 6, $format = 'ALL') {
		switch ($format) {
			case 'ALL' :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
				break;
			case 'CHAR' :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
				break;
			case 'NUMBER' :
				$chars = '0123456789';
				break;
			default :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
				break;
		}
		mt_srand ( ( double ) microtime () * 1000000 * getmypid () );
		$password = "";
		while ( strlen ( $password ) < $len )
			$password .= substr ( $chars, (mt_rand () % strlen ( $chars )), 1 );
		return $password;
	}
	
	/**
	 * 发送邮件
	 * @param unknown $data 邮件数据 $toAddress = '324716355@qq.com',$subject = '邮箱验证',$content = '内容',$fromName = '人人快递'
	 * @param number $type 0 异步 1 同步
	 * @return boolean
	 */
	public static function sendEmail($data = [],$type = 0) {
		if (empty ( $data ) || !is_array($data)) {
			return false;
		}
		if($type == 1){//同步发送
			return CommonEmail::send($data);
		}
		return self::queuePush($data,'email');
	}
	
	
	/**
	 * 发送商家订单数据
	 * @param unknown $data
	 * @param number $type
	 * @author RTS 2017年2月21日 11:59:50
	 * @return boolean
	 */
	public static function sendOrderToBusiness($data = [],$type = 0) {
		if (empty ( $data ) || !is_array($data)) {
			return false;
		}
		if($type == 1){//同步发送
			return CommonBusiness::addOrder($data);
		}
		return self::queuePush($data,'addOrder2Business');
	}
	
	/**
	 * 发送生成订单数据到OpenAPI
	 * @param unknown $data
	 * @param number $type
	 */
	public static function sendOrderToOpenApi($data = [],$type = 0){
		if (empty ( $data ) || !is_array($data)) {
			return false;
		}
		if($type == 1){//同步发送
			return CommonOpenApi::addOrder($data);
		}
		return self::queuePush($data,'addOrder2OpenApi');
	}
	
	/**
	 * 发送取消订单数据到OpenAPI
	 * @param unknown $data
	 * @param number $type
	 */
	public static function sendCancelOrderToOpenApi($data = [],$type = 0){
		if (empty ( $data ) || !is_array($data)) {
			return false;
		}
		if($type == 1){//同步发送
			return CommonOpenApi::cancelOrder($data);
		}
		return self::queuePush($data,'cancelOrder2OpenApi');
	}

	/**
	 * 发送短息
	 *
	 * @param $data 短信数据集
	 * @param $type 0 异步 1 同步
	 * @return boolean
	 */
	public static function sendMobileMsg($data = [],$type = 0) {
		if (empty ( $data ) || !is_array($data)) {
			return false;
		}
		if($type == 1){//同步发送
			return self::sendSms($data);
		}
		return self::queuePush($data,'sms');
	}
	
	/**
	 * 入队列 laterOn
	 * @author RTS 2017年2月6日 13:39:27
	 * @param unknown $data 数据集合
	 */
	public static function queuePush($data = [],$msgTopic = 'sms',$queueName = 'yiiframebase'){
		if(!isset(CommonEnum::$msgTopic[$msgTopic])){
			return false;
		}
		$queueData = ['data' => $data];
		$queueInfo = [
			'msgTopic' => CommonEnum::$msgTopic[$msgTopic],
			'msgTopicTxt' => $msgTopic,
			'msgInsertDate' => date('Y-m-d H:i:s'),
			'msgTopicId' => self::getGuid($msgTopic)
		];
		$queueData['queueInfo'] = $queueInfo;
		try{
			$res = Yii::$app->queue->pushOn(new CommonHandle(),$queueData,$queueName);
			$queueData['queueInfo']['queueId'] = $res;
			self::logQueue($queueInfo['msgTopicId'],$queueData);
			return true;
		}catch (\Exception $ex){
			self::log('加入对列异常:'.$ex->getMessage().'，数据：'.json_encode($queueData,JSON_UNESCAPED_UNICODE),$msgTopic,'queuePush');
			return false;
		}
		
	}
	
	
	/**
	 * 对列queueName
	 * @return string
	 */
	public static function getQueueName(){
		return 'queueDataName--'.date('Y-m-d');
	}
	
	/**
	 * 根据队列ID 再次丢入对列执行 或者直接传数据入队
	 * @param string $id
	 * @return bool
	 */
	public static function addQueue($id = '',$datas = []){
		$ssdb = Yii::$app->ssdb;
		if(!empty($id)){
			$data = $ssdb->hget(self::getQueueName(), $id);
		}
		$data = empty($data) ? $datas : $data;
		if(empty($data)){
			return '数据为空。';
		}
		
		$data = json_decode($data,1);
		$queueInfo = self::getArrayValue($data,'queueInfo',[]);
		if(empty($queueInfo)){
			return '缺少队列信息';
		}
		$qdata = $data['data'];
		$topic = CommonFun::getArrayValue ( $queueInfo, 'msgTopic', - 1 );
		$res = false;
		$msgTopic = CommonEnum::$msgTopic;
		switch ($topic) {
			case $msgTopic['email'] :
				$res = self::sendEmail($qdata);
				break;
			case $msgTopic['sms'] :
				$res = self::sendSms($qdata);
				break;
			case $msgTopic['addOrder2OpenApi'] :
				$res = self::sendOrderToOpenApi();
				break;
			case $msgTopic['cancelOrder2OpenApi'] :
				$res = self::sendCancelOrderToOpenApi($qdata);
				break;
			case $msgTopic['addOrder2Business'] :
				$res = self::sendOrderToBusiness($qdata);
				break;
			default :
				break;
		}
		return $res;
	}
	
	/**
	 * 
	 * 记录对列数据到SSDB 做补偿用
	 * @author RTS 2017年3月1日 15:33:22
	 * @param string $hash
	 * @param string $msgId
	 * @param array $data
	 * @return boolean
	 */
	public static function logQueue($msgId = '', $data = [],$hash = ''){
		if(empty($msgId)){
			return false;
		}
		//$ssdb = \Yii::$app->ssdb;
		$hash = empty($hash)? (self::getQueueName()) : $hash;
		
		$dataStr = json_encode($data,JSON_UNESCAPED_UNICODE);
		$queueInfo = $data['queueInfo'];
		
		self::log('加入对列，对列id：'.$queueInfo['queueId'].'数据：'.$dataStr,$queueInfo['msgTopicTxt'],'queuePush');
		//$ssdb->batch()->hset($hash, $msgId, $dataStr)->zset('z-'.$hash, $msgId, 0)->exec();
		return true;
	}
	
	/**
	 * 更新对列执行结果
	 * @param string $msgId
	 * @param unknown $data
	 * @param string $hash
	 */
	public static function updateLogQueue($msgId = '', $data = [],$hash = ''){
		if(empty($msgId)){
			return false;
		}
		return true;
		$ssdb = \Yii::$app->ssdb;
		$hash = empty($hash)? (self::getQueueName()) : $hash;
		
		$hdata = $ssdb->hget($hash, $msgId);
		if(empty($hdata)){
			return false;
		}
		$hdata = json_decode($hdata,1);
		if(empty($hdata)){
			return false;
		}
		
		$hdata['queueInfo']['queueResult'] = $data;
		$hdata = json_encode($hdata,JSON_UNESCAPED_UNICODE);
		$ssdb->hset($hash, $msgId, $hdata);
		return true;
	}

	
	
	/**
	 * 发送短息
	 *
	 * @param number $phone        	
	 * @param string $content        	
	 * @return boolean
	 */
	public static function sendSms($data = []) {
		if (empty ( $data ) || !is_array ( $data )) {
			return false;
		}
		$phone = CommonFun::getArrayValue($data,'phone','');
		$content = CommonFun::getArrayValue($data,'content','');
		$isMobile = CommonValidate::isMobile($phone);
		if(empty($phone) || !$isMobile){
			return false;
		}
		$url = Yii::$app->params ['RRKDInterfaceHost'] . '/RRKDInterface/Interface/ohterInterface.php';
		$qarr = [ ];
		$qarr ["reqName"] = "SendSMS";
		$qarr ["mobile"] = $phone;
		$qarr ["content"] = $content;
		$qarr ['type'] = 3;
		$res = json_decode ( self::callInterface ( 'SendSMS', $qarr, $url ), true );
		CommonFun::log ( '发送短信结果：' . json_encode ( $res ) . '，手机号：' . $phone . '，内容：' . $content, 'list', 'sendMobileMsg' );
		return $res ['success'] == 'true';
	}
	

	/**
	 * 指派订单
	 * @param unknown $data
	 * @param number $type
	 * @author RTS 2017年4月27日 15:31:17
	 * @return boolean
	 */
	public static function receiveOrder($data = [],$type = 0) {
		if (empty ( $data ) || !is_array($data)) {
			return false;
		}
		if($type == 1){//同步发送
			return CommonBusiness::receiveOrder($data);
		}
		return self::queuePush($data,'receiveOrder','receiveOrder');
	}
	
	/**
	 * 生成token校验身份
	 *
	 * @param type $str        	
	 */
	public static function getToken($str = '', $key = null) {
		if (empty ( $key )) {
			$key = Yii::$app->params ['BusinessKey'];
		}
		return strtolower ( md5 ( $key . strtolower ( md5 ( $str ) ) ) );
	}
	
	/**
	 * 防xss过滤
	 *
	 * @author rentingshuang <tingshuang@rrkd.cn>
	 * @param type $string        	
	 * @param type $low        	
	 * @return boolean
	 */
	public static function cleanXss(&$string, $low = False) {
		if (! is_array ( $string )) {
			$string = trim ( $string );
			$string = strip_tags ( $string );
			$string = htmlspecialchars ( $string );
			if ($low) {
				return $string;
			}
			$string = str_replace ( array (
					'"',
					"'",
					"..",
					"../",
					"./",
					'/',
					"//",
					"<",
					">" 
			), '', $string );
			$no = '/%0[0-8bcef]/';
			$string = preg_replace ( $no, '', $string );
			$no = '/%1[0-9a-f]/';
			$string = preg_replace ( $no, '', $string );
			$no = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';
			$string = preg_replace ( $no, '', $string );
			return $string;
		}
		$keys = array_keys ( $string );
		foreach ( $keys as $key ) {
			self::cleanXss ( $string [$key] );
		}
	}
	
	/**
	 * 校验前端数据合法性token
	 *
	 * @param string $str        	
	 * @param string $encode        	
	 * @param string $csrf        	
	 * @author RTS 2016年4月18日 10:03:11
	 */
	public static function vToken($str = '', $encode = '', $csrf = '') {
		if (empty ( $str ) || empty ( $encode ) || empty ( $csrf )) {
			return false;
		}
		$decode = self::getAesPwd ( $encode, $csrf );
		$res = $str == $decode;
		$cip = self::getClientIP ();
		CommonFun::log ( 'IP:' . $cip . '数据校验' . ($res ? '正确' : '失败') . '，传入密码：' . $encode . '，正确串为：' . $decode . '，传入串：' . $str, 'vToken', 'vToken' );
		if (! $res) {
			// Fun::log ( 'IP:'.$cip.'数据校验失败，正确串为：' . $decode . '，传入串：' . $str, 'vToken', 'vToken' );
		}
		return $res;
	}
	
	/**
	 * 数组按某字段排序
	 * @param unknown $arr
	 * @param string $order
	 * @return boolean
	 */
	public static function arrayMultisort($arr = [], $flg = 'order', $order = SORT_ASC) {
		$flag = array ();
		foreach ( $arr as $k => $arr2 ) {
			$flag [] = isset ( $arr2 [$flg] ) ? $arr2 [$flg] : $k;
		}
		array_multisort ( $flag, $order, $arr );
		return $arr;
	}
	
	/**
	 * 获取解密后密码
	 *
	 * @param unknown $str        	
	 */
	public static function getAesPwd($str, $csrf = '') {
		if (empty ( $str )) {
			return '';
		}
		$csrf = substr ( $csrf, 0, 16 );
		if (empty ( $csrf )) {
			return '';
		}
		try {
			$iv = $privateKey = $csrf;
			// $iv = $privateKey = Yii::$app->params['Aes']['key'];
			$encryptedData = base64_decode ( $str );
			$decrypted = mcrypt_decrypt ( MCRYPT_RIJNDAEL_128, $privateKey, $encryptedData, MCRYPT_MODE_CBC, $iv );
			return trim ( $decrypted );
		} catch ( \Exception $ex ) {
			return '';
		}
	}
	
	/**
	 * 记录日志
	 *
	 * @param type $msg
	 *        	@time 2015年8月31日17:46:20
	 * @author rentingshuang <tingshuang@rrkd.cn>
	 */
	public static function log($msg = '', $fileName = '', $dir = '') {
		$directory = date ( 'Y-m-d' );
		$fileName = ! empty ( $fileName ) ? ($fileName . '.log') : ($directory . 'app.log');
		if (! empty ( $dir )) {
			$directory .= DIRECTORY_SEPARATOR . $dir;
		}
		
		$log = new FileTarget ();
		if (! empty ( Yii::$app->params ['LogDir'] )) {
			$log->logFile = Yii::$app->params ['LogDir'] . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $fileName;
		} else {
			$log->logFile = Yii::$app->getRuntimePath () . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $fileName;
		}
		if (! is_string ( $msg )) {
			$msg = json_encode ( $msg,JSON_UNESCAPED_UNICODE );
		}
		$log->messages [] = [ 
				$msg 
		];
		$log->export ();
	}
	
	/**
	 * 返回失败JSON
	 *
	 * @author rentingshuang <tingshuang@rrkd.cn>
	 *         @time 2015年8月20日17:46:20
	 * @param type $msg        	
	 */
	public static function returnFalse($msg = '', $data = [], $code = -1) {
		$msg = empty ( $msg ) ? '系统繁忙，请稍后再试' : $msg;
		$s = json_encode ( array_merge ( [ 
				'status' => 0,
				'success' => 'false',
				'msg' => $msg,
				'code' => $code 
		], $data ), JSON_UNESCAPED_UNICODE );
		exit ( $s );
	}
	
	/**
	 * 返回成功JSON
	 *
	 * @param type $data        	
	 */
	public static function returnSuccess($data = []) {
		$s = json_encode ( array_merge ( [ 
				'status' => 1,
				'success' => 'true',
				'code' => 0 
		], $data ), JSON_UNESCAPED_UNICODE );
		exit ( $s );
	}
	
	
	
	/**
	 * 调用基础服务接口
	 *
	 * @param string $code
	 *        	接口编码
	 * @param unknown $data
	 *        	数据
	 */
	public static function callBaseInterface($code = 'A0001', $data = [], $isPost = true) {
		$return = [ 
				'success' => false 
		];
		if (empty ( $code )) {
			$return ['msg'] = '缺少接口编码code';
			return $return;
		}
		$header = [ ];
		$interfaceInfo = Yii::$app->params ['Interface'];
		if (! isset ( $interfaceInfo ['List'] ) || ! isset ( $interfaceInfo ['List'] [$code] ) || empty ( $interfaceInfo ['Host'] )) {
			$return ['msg'] = '缺少接口编码：' . $code . '所对的配置项';
			return $return;
		}
		
		$temp = $interfaceInfo ['List'] [$code];
		$host = $interfaceInfo ['Host'] ['base'];
		if (is_array ( $temp )) {
			$host = $interfaceInfo ['Host'] [$temp [0]];
			$temp = $temp [1];
		}
		$url = $host . $temp;
		if (! $isPost) { // GET 需要组装参数
			$url .= '?';
			$temp = [ ];
			foreach ( $data as $k => $t ) {
				$temp [] = $k . '=' . $t;
			}
			$url .= implode ( '&', $temp );
			$res = self::curlGet ( $url, 10, $header );
		} else {
			$res = self::curlPost ( $url, $data, 10, $header, 0 );
		}
		self::log ( ($isPost ? 'POST' : 'GET') . ' 请求baseURL：' . $url . '，数据：' . json_encode ( $data, JSON_UNESCAPED_UNICODE ) . '结果：' . $res, $code, 'callBaseInterface' );
		$datas = CommonValidate::isJson ( $res );
		if ($datas === false || ! isset ( $datas ['success'] )) {
			$return ['msg'] = '接口编码：' . $code . '远程服务器[' . $url . ']未响应或响应不正确';
			self::log ( '请求baseURL：' . $url . '，结果：' . $res, 'failed', 'callBaseInterfaceFailed' );
			return $return;
		}
		
		return $datas;
	}
	
	/**
	 * POST RRKD远程接口
	 *
	 * @param type $reqName
	 *        	请求名称
	 * @param type $data
	 *        	//发送的数据
	 * @param type $url
	 *        	//地址
	 * @param type $rrkdKey
	 *        	//key
	 * @return type //json str
	 */
	public static function callInterface($reqName, $data, $url = '', $rrkdKey = 'RrkdHttpTools', $token = '', $userName = '',$header = []) {
		$TIMESTAMP = time ();
		$timeout = 60;
		$pdatype = 4;
		$vision = '1.0.0';
		$is_file = (isset ( $data ['json'] ) && ! empty ( $data ['json'] )) ? true : false;
		if (! $is_file) {
			$data ["pdatype"] = $pdatype;
			$data ['reqName'] = $reqName;
			$data ['version'] = $vision;
			$data = json_encode ( $data );
			$data = base64_encode ( md5 ( $TIMESTAMP . $rrkdKey ) . "@_@" . $data );
		} else {
			$data ['json'] ["pdatype"] = $pdatype;
			$data ['json'] ['reqName'] = $reqName;
			$data ['json'] ['version'] = $vision;
			$data ['json'] = json_encode ( $data ['json'] );
			$data ['json'] = base64_encode ( md5 ( $TIMESTAMP . $rrkdKey ) . "@_@" . $data ['json'] );
		}
		if(empty($header)){
			$header = array ();
			$header [] = $is_file ? "Content-Type: multipart/form-data" : "Content-Type: application/json";
			$header [] = "username:" . $userName;
			$header [] = "token:" . $token;
		}
		$header [] = "TIMESTAMP:" . $TIMESTAMP;

		if ($is_file && ! empty ( $_FILES ) && ! empty ( $_FILES ['fname'] ['tmp_name'] )) {
			$filename = $_FILES ['fname'] ['name'];
			$path = $_FILES ['fname'] ['tmp_name'];
			$type = $_FILES ['fname'] ['type'];
			if (class_exists ( '\CURLFile' )) {
				$data ['pic'] = curl_file_create ( realpath ( $path ), $type, $filename );
			} else {
				$data ['pic'] = '@' . realpath ( $path ) . ";type=" . $type . ";filename=" . $filename;
			}
		}
		return self::curlPost ( $url, $data, $timeout, $header, $is_file );
	}
	
	/**
	 * CURL POST数据
	 *
	 * @param string $url
	 *        	发送地址
	 * @param array $post_data
	 *        	发送数组
	 * @param integer $timeout
	 *        	超时秒
	 * @param string $header
	 *        	头信息
	 * @return string
	 */
	static public function curlPost($url, $post_data = array(), $timeout = 15, $header = array(), $Post_File = false,$times = 0) {
		$post_string = null;
		if (is_array ( $post_data ) && ! $Post_File) {
			$post_string = http_build_query ( $post_data );
		} else {
			$post_string = $post_data;
		}

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_string );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header ); // 模拟的header头
		
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE ); // https请求 不验证证书和hosts
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		
		$curRetryTimes = $times;
		do {
			$result = curl_exec ( $ch );
			$curRetryTimes --;
		} while ( $result === FALSE && $curRetryTimes >= 0 );
		
		
		curl_close ( $ch );
		return $result;
	}
	
	/**
	 * CURL GET
	 *
	 * @param type $url        	
	 * @param type $timeout        	
	 * @param type $header        	
	 * @return type
	 */
	static public function curlGet($url, $timeout = 5, $header = array(),$times = 0) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
		
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE ); // https请求 不验证证书和hosts
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		
		
		$curRetryTimes = $times;
		do {
			$result = curl_exec ( $ch );
			$curRetryTimes --;
		} while ( $result === FALSE && $curRetryTimes >= 0 );
		
		curl_close ( $ch );
		return $result;
	}
	
	/**
	 * 设置缓存
	 *
	 * @param unknown $cache_name        	
	 * @param number $time        	
	 */
	static public function setCache($cache_name = '', $value = '', $time = 0) {
		$cache = Yii::$app->cache;
		$cache_name = self::getCacheName ( $cache_name );
		$cache->set ( $cache_name, $value, $time );
		return true;
	}
	
	/**
	 * 以什么开头
	 * @param string $str
	 * @param string $needle
	 * @return boolean
	 */
	static public function startWith($str = '',$needle = '',$ignoreCase = false){
		if($ignoreCase){
			$str = strtolower($str);
			$needle = strtolower($needle);
		}
		return strpos($str, $needle) === 0;
	}
	
	/**
	 * 获取缓存名字 MD5 CacheVersion方便清理缓存
	 *
	 * @param type $name        	
	 * @return type
	 */
	static public function getCacheName($name = '') {
		return md5 ( $name . Yii::$app->params ['CacheVersion'] );
	}
	
	/**
	 * 获取缓存
	 *
	 * @param type $cache_name        	
	 * @return boolean
	 */
	static public function getCache($cache_name) {
		$cache = Yii::$app->cache;
		$cache_name = self::getCacheName ( $cache_name );
		$cache_value = $cache->get ( $cache_name );
		if (!empty($cache_value)) {
			return $cache_value;
		}
		return '';
	}


	/**
	 * 生成URL
	 *
	 * @param string $url        	
	 * @param string $scheme        	
	 * @return Ambigous <string, unknown, boolean, string, multitype:, mixed>
	 */
	public static function url($url = [], $scheme = false) {
		return Url::to ( $url, $scheme );
	}
	
	/**
	 * 实例化一个分页对象
	 *
	 * @author LM
	 * @param int $pageSize        	
	 * @param int $count        	
	 * @param array $params
	 *        	分页url添加的参数.例:$params['time'] = xxxx;
	 */
	public static function pagination($pageSize, $count, $params = []) {
		$pagination = new \yii\data\Pagination ( [ 
				'defaultPageSize' => $pageSize,
				'pageSize' => $pageSize,
				'totalCount' => $count 
		] );
		$pagination->params = array_merge ( $_GET, $params );
		return $pagination;
	}
	
	/**
	 * 获取文件的后缀名
	 *
	 * @param unknown $file        	
	 */
	public static function getExtension($file) {
		return substr ( $file, strrpos ( $file, '.' ) + 1 );
	}
	
	/**
	 * 异步对列curl
	 *
	 * @param array||json_str $data        	
	 * @param number $type        	
	 * @author RTS 2017年9月7日 09:44:35
	 * @return string
	 */
	public static function queueCurl($data = [], $type = 0) {
		if (is_string ( $data )) {
			$data = CommonValidate::isJson ( $data );
		}
		if (empty ( $data )) {
			return false;
		}
		if ($type == 1) { // 同步发送
			$curlType = self::getArrayValue ( $data, 'type', 0 ); // 0 POST 1 GET
			$url = self::getArrayValue ( $data, 'url', '' );
			$timeout = self::getArrayValue ( $data, 'timeout', 10 );
			$header = self::getArrayValue ( $data, 'header', [ ] );
			$reTryTimes = self::getArrayValue ( $data, 'reTryTimes', 0 ); // 重试次数
			$sendData = self::getArrayValue ( $data, 'data', [ ] );
			if (is_array ( $sendData )) {
				$sendData = json_encode ( $sendData );
			}
			
			if ($curlType == 1) {
				return self::curlGet ( $url, $timeout, $header, $reTryTimes );
			}
			return self::curlPost ( $url, $sendData, $timeout, $header, 0, $reTryTimes );
		}
		return self::queuePush($data,'curl','yiiBaseDo');
	}
	
	
	/**
	 * mongoDB操作 提供插入 更新 暂不提供delele
	 * @param unknown $data
	 * @param number $type
	 * @return boolean
	 */
	public static function doMongoDB($data = [], $type = 0) {
		if (is_string ( $data )) {
			$data = CommonValidate::isJson ( $data );
		}
		if (empty ( $data )) {
			return false;
		}
		try {
			if ($type == 1) { // 同步发送
				$doType = self::getArrayValue ( $data, 'type', 0 ); // 0 insert 1 update 2 删除 3 创建索引 createIndex 
				$dbName = self::getArrayValue ( $data, 'databaseName', 'rrkd_yiiframework' );
				$collect = self::getArrayValue ( $data, 'collection', 'checkMongo' );
				$query = self::getArrayValue ( $data, 'query', [ ] );
				$mgData = self::getArrayValue ( $data, 'data', [] ); // 如果是insert 则是新数据 如果是update 则是新数据
				$mongodb = Yii::$app->mongodb;
				$mongodb->defaultDatabaseName = $dbName;
				$collection = $mongodb->getCollection ( $collect );
				$res = false;
				
				if(is_string($mgData)){
					$mgData = CommonValidate::isJson($mgData);
				}
				
				switch ($doType) {
					case 0 :
						$res = $collection->insert ( $mgData );
						break;
					case 1 :
						$res = $collection->update ( $query, $mgData );
						break;
					case 2 :
						$res = $collection->remove( $query );
						break;
					case 3 :
						$res = $collection->createIndex( $mgData );
						break;
					default:
						break;	
				}
				$mongodb->close ();
				return $res;
			}
			
			return self::queuePush($data,'mongo','yiiBaseDo');
		}catch (\Exception $e){
			if(isset($mongodb)){
				$mongodb->close ();
			}
			self::log ( $e->getMessage (), __FUNCTION__, __FUNCTION__ );
			return false;
		}
	}
}

?>