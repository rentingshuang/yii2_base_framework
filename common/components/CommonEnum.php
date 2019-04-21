<?php

namespace common\components;

/**
 * 公用枚举
 * @author RTS 2017年2月6日 13:14:56
 *
 */
class CommonEnum {

	/**
	 * 消息类型枚举
	 * 
	 */
    public static $msgTopic = [
        'email' => 0, //邮件
        'sms' => 1,  //短信
        'addOrder2OpenApi' => 2,//openAPI插入数据
        'cancelOrder2OpenApi' => 3,//取消openAPI订单
        'addOrder2Business'=> 4,//商家发单
        'receiveOrder' => 5,//快递员派单
        'curl' => 6,//curl请求
	];
}

?>