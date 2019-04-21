<?php
$main = [ 
		'charset' => 'UTF-8',
		'vendorPath' => dirname ( dirname ( __DIR__ ) ) . '/vendor',
		// 'defaultController'=>'Index/index',
		'defaultRoute' => 'default/index',
		'components' => [ 
				/*
				'urlManager' => [ 
						'enablePrettyUrl' => true,
						'showScriptName' => false,
						'suffix' => '.html',
						'rules' => [ 
								'user/<action:\w+>' => 'user/default/<action>' 
						] 
				],
				*/
				'request' => [ 
						'cookieValidationKey' => 'yiibaseframework',
						'parsers' => [ 
								'application/json' => 'yii\web\JsonParser',
								'text/json' => 'yii\web\JsonParser' 
						] 
				],
				
				'log' => [ 
						'traceLevel' => YII_DEBUG ? 3 : 0,
						'targets' => [ 
								[ 
										'class' => 'yii\log\FileTarget',
										'levels' => [ 
												'error',
												'warning' 
										] 
								] 
						] 
				],
				
				/*
				'errorHandler' => [ 
						'errorAction' => 'error' 
				],
				
				*/
		],
		'params' => require (__DIR__ . '/params.php') 
];

return yii\helpers\ArrayHelper::merge ( $main, require (__DIR__ . '/db.php') );
