<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;


/**
 * 配置检测公用类
 * @author RTS 2017年4月18日 11:28:27
 * 
 */
class ConfigController extends Controller {
	public $enableCsrfValidation = false;
	public function init() {
		
	}
	public function actions() {
		return [
			'index' => [
				'class' => 'light\swagger\RRKDConfigAction',
			],
		];
	}

	
}
