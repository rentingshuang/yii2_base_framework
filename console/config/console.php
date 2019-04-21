<?php
$params = require (__DIR__ . '/../../common/config/params.php');
$db = require (__DIR__ . '/../../common/config/db.php');

$params['LogDir'] = '';

$main = [ 
		'id' => 'common-console',
		'basePath' => dirname ( __DIR__ ),
		'bootstrap' => [ 
				'log',
		],
		'controllerNamespace' => 'console\commands',
		'vendorPath' => __DIR__ . '/../../vendor',
		'modules' => [ 
				'gii' => 'yii\gii\Module' 
		],
		'components' => [ 
				'cache' => [ 
						'class' => 'yii\caching\FileCache' 
				],
				'log' => [ 
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
		],
		'params' => $params,
		'controllerMap' => [
			'baseListen' => [
				'class' => 'console\commands\YiiBaseQueueController'
			]
		]
		 
];

return yii\helpers\ArrayHelper::merge ( $db,$main );
