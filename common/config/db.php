<?php
if (! class_exists ( 'Qconf' )) {
	return [ 
			'components' => [
			//落地配数据库					
					'db'=>[
						'class' => 'yii\db\Connection',
						'dsn' => 'mysql:host=localhost;dbname=rk',
						'username' => 'root',
						'password' => 'root',
						'charset' => 'utf8'
					],
					
					'cache' => [
						'class' => 'yii\redis\Cache',
						'keyPrefix' => 'yiiframework:'
					],
					
					/*
					'cache' => [
						'class' => 'wsl\ssdb\Cache',
						'ssdb' => 'ssdb'
					],
					*/
	
	
				'redis' => [
					'class' => 'yii\redis\Connection',
					'hostname' => 'localhost',
					'port' => 6379,
					'password' => '',
					'database' => 0, //缓存和默认
				],
						
					
					
				
					/*
					'ssdb' => [
						'class' => 'wsl\ssdb\Connection',
						'host' => '172.16.60.12',
						'port' => 8888,
					],
					*/
					//'dsn' => 'mongodb://developer:password@localhost:27017/mydatabase',
					'mongodb' => [
						'class' => '\yii\mongodb\Connection',
						//'dsn' => 'mongodb://172.16.60.12:27017',
						
						'dsn' => 'mongodb://rwuser:A*mi75xqa@172.16.60.12:27017',
						
						'options'=>[
							'db'=>'rrkd_yiiframework',
						    'authMechanism' => 'MONGODB-CR',
						    'authSource' => 'admin',
	        			],
	        			
					],
					
					
					
					'gearman' => array(
							'class' => 'yii\caching\Gearman',
							'servers' => array(
									array('host' => '127.0.0.1', 'port' => 4730),
							),
					),
					
					
					'queue' => [ 
							'class' => 'shmilyzxt\queue\queues\BeanstalkdQueue',
							'jobEvent' => [ ],
							'connector' => [  // 需要安装 pad\pheanstalk 扩展来操作beastalkd
									'class' => 'shmilyzxt\queue\connectors\PheanstalkConnector',
									'host' => '172.16.60.12',
									'port' => 11300 
							],
							'queue' => 'yiiframebase',
							'expire' => 60,
							'maxJob' => 0,
							'failed' => [ 
									'logFail' => false,
									'provider' => [ ] 
							] 
					] 
			] 
	];
}

return [ 
		'components' => [ 
				'db' => [ 
						'class' => 'yii\db\Connection',
						'dsn' => 'mysql:host=' . Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/hostname" ) . ':' . Qconf::getConf ( "/system_cfg/MySQL/slave_rrkd/port" ) . ';dbname=' . Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/dbname" ),
						'username' => Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/username" ),
						'password' => Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/password" ),
						'charset' => 'utf8' 
				],
				
				//落地配数据库
				'ldpdb'=>[
					'class' => 'yii\db\Connection',
					'dsn' => 'mysql:host=' . Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/hostname" ) . ':' . Qconf::getConf ( "/system_cfg/MySQL/slave_rrkd/port" ) . ';dbname=' . Qconf::getConf ( "/system_cfg/MySQL/master_ldp_rrkd_php/dbname" ),
					'username' => Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/username" ),
					'password' => Qconf::getConf ( "/system_cfg/MySQL/master_rrkd/password" ),
					'charset' => 'utf8'
				],
				'redis' => [ 
						'class' => 'yii\redis\Connection',
						'hostname' => Qconf::getConf ( "/system_cfg/NoSQL/Redis/hostname" ),
						'port' => Qconf::getConf ( "/system_cfg/NoSQL/Redis/port" ),
						'password' => Qconf::getConf ( "/system_cfg/NoSQL/Redis/password" ),
						'database' => 0 
				],
				
				'redis2' => [
					'class' => 'yii\redis\Connection',
					'hostname' => Qconf::getConf ( "/system_cfg/NoSQL/redis2/hostname" ),
					'port' => Qconf::getConf ( "/system_cfg/NoSQL/redis2/port" ),
					'password' => Qconf::getConf ( "/system_cfg/NoSQL/redis2/password" ),
					'database' => 0
				],
				
				'mongodb' => [ 
						'class' => '\yii\mongodb\Connection',
						'dsn' => 'mongodb://'.Qconf::getConf ( "/system_cfg/NoSQL/MongoDB/username" ).':'.Qconf::getConf ( "/system_cfg/NoSQL/MongoDB/password" ).'@'.Qconf::getConf ( "/system_cfg/NoSQL/MongoDB/hostname" ) . ':' . Qconf::getConf ( "/system_cfg/NoSQL/MongoDB/port" ),
						'options'=>[
							'db'=>'rrkd_yiiframework',
							'authMechanism' => 'MONGODB-CR',
							'authSource' => 'admin',
	        			],
				],

				'ssdb' => [
					'class' => 'wsl\ssdb\Connection',
					'host' => Qconf::getConf("/system_cfg/NoSQL/SSDB/hostname"),
					'port' => Qconf::getConf("/system_cfg/NoSQL/SSDB/port"),
				],
				
				'cache' => [ 
						'class' => 'yii\redis\Cache',
						'keyPrefix' => 'yiiframework:' 
				],
				
				'gearman' => [
					'class' => 'yii\caching\Gearman',
					'servers' => [
						[
						   'host' => Qconf::getConf ( "/system_cfg/NoSQL/gearmand/hostname" ), 'port' => Qconf::getConf ( "/system_cfg/NoSQL/gearmand/port" )
						],
					],
				],
				
				'queue' => [ 
						'class' => 'shmilyzxt\queue\queues\BeanstalkdQueue',
						'jobEvent' => [ ],
						'connector' => [  // 需要安装 pad\pheanstalk 扩展来操作beastalkd
								'class' => 'shmilyzxt\queue\connectors\PheanstalkConnector',
								'host' => Qconf::getConf ( "/system_cfg/Queue/Beanstalkd2/hostname" ),
								'port' => Qconf::getConf ( "/system_cfg/Queue/Beanstalkd2/port" ) 
						],
						'queue' => 'yiiframebase',
						'expire' => 60,
						'maxJob' => 0,
						'failed' => [ 
								'logFail' => false,
								'provider' => [ ] 
						] 
				] 
		] 
];