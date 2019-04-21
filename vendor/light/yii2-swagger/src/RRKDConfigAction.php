<?php

namespace light\swagger;

use yii\base\Action;
use common\components\CommonFun;
use common\components\CommonSpyc;
use common\components\CommonValidate;
use common\models\CommonHandle;

class RRKDConfigAction extends Action {
	public function run() {
		$this->controller->layout = false;
		$request = \Yii::$app->request;
		if (CommonValidate::isAjax () && CommonValidate::isPost ()) {
			return $this->checkAvailable ( $request->post ( 'type', '' ) );
		}
		$components = \Yii::$app->components;
		$config = [ 
				'Params' => \Yii::$app->params,
				'Components' => $components 
		];
		
		if(!isset($config['Params']['ParamsDEBUG']) || !$config['Params']['ParamsDEBUG']){
			echo 'DEBUG:FALSE';
			exit;
		}
		
		$view = $this->controller->getView ();
		return $view->renderFile ( __DIR__ . '/config.php', [ 
				'cfg' => $config 
		], $this->controller );
	}
	
	/**
	 * 校验配置是否可用
	 *
	 * @author RTS 2017年4月17日 13:56:31
	 */
	public function checkAvailable($type = 'db') {
		if (empty ( $type )) {
			return CommonFun::returnFalse ( 'NO TYPE' );
		}
		$type = strtolower ( $type );
		$res = true;
		switch ($type) {
			case 'db' :
				$res = $this->checkDB ();
				break;
			case 'cache' :
				$res = $this->checkCache ();
				break;
			case 'mongodb' :
				$res = $this->checkMongo ();
				break;
			case 'gearman' :
				$res = $this->checkGearman ();
				break;
			case 'ssdb' :
				$res = $this->checkSSDB ();
				break;
			case 'queue' :
				$res = $this->checkQueue ();
				break;
		}
		$type = gettype($res);
		if ($type == 'string') {
			return CommonFun::returnFalse ( $res );
		}
		return CommonFun::returnSuccess ();
	}
	
	/**
	 * db
	 *
	 * @return boolean
	 */
	public function checkDB() {
		try {
			$db = \Yii::$app->db;
			$db->open ();
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
			exit;
		}
		return true;
	}
	
	/**
	 * cache
	 *
	 * @return boolean
	 */
	public function checkCache() {
		try {
			$cache = \Yii::$app->cache;
			$cache->set ( 'checkCache', 'xxxx', 60 );
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
			exit;
		}
		return true;
	}
	
	/**
	 * Mongo
	 *
	 * @return boolean
	 */
	public function checkMongo() {
		try {
			
			$mongodb = \Yii::$app->mongodb;
			$collection = $mongodb->getCollection ( 'checkMongo' );
			$collection->insert(['test'=>'system']);
			
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
			exit;
		}
		return true;
	}
	
	/**
	 * Gearman
	 *
	 * @return boolean
	 */
	public function checkGearman() {
		try {
			$worker = \Yii::$app->gearman->worker();
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
			exit;
		}
		return true;
	}
	
	/**
	 * checkSSDB
	 *
	 * @return boolean
	 */
	public function checkSSDB() {
		try {
			$cache = \Yii::$app->cache;
			$cache->set ( 'checkCache', 'xxxx', 60 );
		} catch ( \Exception $e ) {
			return $e->getMessage ();
		}
		return true;
	}
	/**
	 * checkSSDB
	 *
	 * @return boolean
	 */
	public function checkQueue() {
		try {
			$res = \Yii::$app->queue->pushOn(new CommonHandle(),['test'=>'checkQueue'],'checkQueue');
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
			exit;
		}
		return true;
	}
}
