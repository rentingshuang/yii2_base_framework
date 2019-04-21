<?php

return [
	'RRKDInterfaceHost' => class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/inter/phpInterUrl" )): 'http://172.16.60.12:9439', // 人人快递接口地址
	'FreeManInterfaceHost' => class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/inter/fmUrl" )): 'http://172.16.60.12:9438', // 自由人接口地址
	'OrderNumberServer' => class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/Common/config/NumberApi" )):'',//订单号生成服务
	'KFSystemHost'=> class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/inter/kfInterUrl" )):'',
	'OperateSystemHost'=> class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/inter/operateServiceApiUrl" )):'http://172.16.20.221:8982',
	
	'CacheVersion' => 1.0,
    'Mailer'=>[
    	'username' => 'credit@rrkd.cn',
    	'password' => 'Cwkj2016',
    ],
    'OpenApi'=>[
    	'host'=> class_exists('Qconf')? (Qconf::getConf ( "/interface_cfg/openapi_url" )): 'http://127.0.0.6',
    	
    	'list'=>[
    		'addorder'=>'/addorderfortdd',
    		'cancelorder'=>'/cancelorder',
    	],
    ],
    'Pay'=>[
    	'url'=> class_exists('Qconf')? (Qconf::getConf ( "/interface_cfg/paycenter_url" ).'/pay/pay/pay'): 'http://118.122.117.66:8810/pay/pay/pay',//支付中心地址
    ],
    
    'AesKey' =>  class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/Common/config/AESKEY" )): '4AD3C67449AB11E6A1ED005056842D8F',
    'BaiduAk'=> 'IkSvwkWPwCuICyAjnS0QGBzw',
    'ParamsDEBUG'=> 0,//是否开启配置展示模式
    //'LogDir' => '/Log',//目录日志
    

	'TakeOutService' => class_exists('Qconf')? (Qconf::getConf ( "/system_cfg/inter/takeOutService" )): 'http://172.16.60.12:9439',//外卖导单服务
];
