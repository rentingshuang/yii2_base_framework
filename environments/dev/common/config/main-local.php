<?php
return [ 
		'components' => [ 
				'db' => [ 
						'class' => 'yii\db\Connection',
						'dsn' => 'mysql:host=172.16.60.12;dbname=56CityExpress',
						'username' => 'root',
						'password' => 'rt@%12W*xy',
						'charset' => 'utf8',
				],
				
				'redis' => [ 
						'class' => 'yii\redis\Connection',
						'hostname' => '172.16.60.12',
						'port' => 6379,
						'password' => 'cwkj0987',
						'database' => 0 
				],
				
				'mongodb' => [ 
						'class' => '\yii\mongodb\Connection',
						'dsn' => 'mongodb://172.16.60.12:27017/rrkd_credit' 
				] 
		] 
]
;
