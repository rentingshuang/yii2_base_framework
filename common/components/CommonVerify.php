<?php

namespace common\components;

use Yii;
/*
 * 验证码类 设置参数可以在实例化时传入： $vcode=new Verify(array("width"=>100,"height"=>35,"fontfiles"=>array("your.ttf","your2.ttf"))); 获得验证码getCode(); 获得图片getImg()必须先调用getCode(); 支持设置的属性width,height,count,bgimg:背景图片URL,type:格式(gif...), str:验证码组合类型(0纯数字 1纯字母 2混合) fontfiles:字体文件数组(每个码随机抽取不同的字体) point=>15表示每15平方像素一个干扰点 arc=>400表示每400平方像素-个干扰弧线
 */
class CommonVerify {
	private $vcode = null; // 验证码
	private $img = null; // 验证码图片
	private $width = 116; // 宽度
	private $height = 35; // 高度
	private $count = 4; // 个数
	private $bgimg = null; // 背景图片
	private $type = "gif"; // 返回的图片格式gif jpg png
	private $str = 0; // 字符串组合类型 0纯数字 1纯字母 2混合
	private $fontfiles; // 字体数组(每个字符随机抽取不同的字体)
	private $timeOut = 600;
	private $code = array (
			"0123456789",
			"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghiklmnopqrstuvwxyz",
			"ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghikmnprstuvwxyz01234567890123456789" 
	); // 随机因子
	private $pmarr = array (
			"width",
			"height",
			"count",
			"bgimg",
			"type",
			"str",
			"fontfiles",
			"point",
			"arc" 
	);
	private $rgb = array (); // 随机颜色
	private $point = 20000; // 每15平方像素一个干扰点
	private $arc = 100000; // 每400平方像素-个干扰弧线
	private $CAPTCHA_ID = 'b46d1900d0a894591916ea94ea91bd2c';
	private $PRIVATE_KEY = '36fc3fe98530eea08dfc6ce76e3d24c4';
	private $MOBILE_CAPTCHA_ID = '7c25da6fe21944cfe507d2f9876775a9';
	private $MOBILE_PRIVATE_KEY = 'f5883f4ee3bd4fa8caec67941de1b903';
	
	// 构造方法
	function __construct($arr = null) {
		$this->setpm ( $arr ); // 设置参数
		$path = Yii::$app->getBasePath ();
		
		$arr = array (
				$path . '/web/font/SpicyRice.ttf' 
		// $path . '/web/font/1.ttf',
		// $path . '/web/font/3.ttf',
				);
		
		$this->fontfiles = empty ( $this->fontfiles ) ? $arr : $this->fontfiles;
	}
	function getCode() {
		$this->makeCode (); // 生成验证码
		return $this->vcode;
	}
	
	/**
	 * 生成验证码
	 *
	 * @param string $scene
	 *        	场景
	 * @param number $type
	 *        	0 传统数字字母类型 1.新型拖动码
	 * @param string $source
	 *        	适用端 0.pc 1.mobile
	 */
	function show($scene = 'login', $type = 0, $source = 0) {
		$cache = Yii::$app->cache;
		$key = $scene . $this->getClientId ();
		if ($type == 0) {
			$code = $this->getCode ();
			$cache->serializer = false;
			$cache->set ( $key, $code, $this->timeOut );
			$cache->serializer = null;
			$this->getImg ();
		} else {
			if ($source == 0) {
				$GtSdk = new GeetestLib ( $this->CAPTCHA_ID, $this->PRIVATE_KEY );
			} else {
				$GtSdk = new GeetestLib ( $this->MOBILE_CAPTCHA_ID, $this->MOBILE_PRIVATE_KEY );
			}
			$status = $GtSdk->pre_process ( $key );
			$cache->set ( 'gtserver', $status, 600 );
			echo $GtSdk->get_response_str ();
		}
	}
	
	/**
	 * 校验
	 *
	 * @param string $code        	
	 * @param string $scene        	
	 * @return boolean
	 */
	function check($code = '', $scene = 'login', $type = 0, $source = 0, $extendData = []) {
		$cache = Yii::$app->cache;
		$key = $scene . $this->getClientId ();
		if ($type == 0) {
			$cache->serializer = false;
			$c = $cache->get ( $key );
			$cache->serializer = null;
			if (empty ( $c )) {
				return false;
			}
			return strtolower ( $c ) == strtolower ( $code );
		} else {
			if ($source == 0) {
				$GtSdk = new GeetestLib ( $this->CAPTCHA_ID, $this->PRIVATE_KEY );
			} else {
				$GtSdk = new GeetestLib ( $this->MOBILE_CAPTCHA_ID, $this->MOBILE_PRIVATE_KEY );
			}
			$gtserver = $cache->get ( 'gtserver' );
			if ($gtserver == 1) {
				$result = $GtSdk->success_validate ( $extendData ['geetest_challenge'], $extendData ['geetest_validate'], $extendData ['geetest_seccode'], $key );
			} else {
				$result = $GtSdk->fail_validate ( $extendData ['geetest_challenge'], $extendData ['geetest_validate'], $extendData ['geetest_seccode'] );
			}
			return $result;
		}
	}
	
	/**
	 * clientId
	 *
	 * @return string
	 */
	function getClientId($name = 'credit_client_id') {
		$c = Fun::cookie ( $name );
		if (empty ( $c )) {
			$c = Fun::getGuid ($name);
		}
		Fun::cookie ( $name, $c, 999 );
		return $c;
	}
	
	// 获取验证码图片
	function getImg() {
		$this->randColor (); // 随机色
		$this->createImg (); // 创建背景
		$this->createObstruct2 (); // 设置干扰元素
		$this->createCode (); // 生成验证码
		$this->createObstruct1 (); // 设置干扰元素
		$this->outputImg (); // 输出图像并销毁
	}
	
	// 设置参数
	private function setpm($arr) {
		if (empty ( $arr )) {
			return false;
		}
		foreach ( $arr as $key => $val ) {
			$key = strtolower ( $key );
			if (in_array ( $key, $this->pmarr )) {
				$this->$key = $val;
			}
		}
	}
	
	// step1创建图像背景
	private function createImg() {
		$bgimg = null;
		$this->img = imagecreatetruecolor ( $this->width, $this->height );
		$s = $this->rgb [0]; // 随机开始
		$e = $this->rgb [1]; // 随机结束
		                     
		// $bgcolor = imagecolorallocate ( $this->img, rand ( $s, $e ), rand ( $s, $e ), rand ( $s, $e ) );
		$bgcolor = imagecolorallocate ( $this->img, ( int ) (0xffffff % 0x1000000 / 0x10000), ( int ) (0xffffff % 0x10000 / 0x100), 0xffffff % 0x100 );
		
		imagefill ( $this->img, 0, 0, $bgcolor );
		// 判断是否有背景图片
		if ($this->bgimg) {
			$bgimgname = $this->bgimg;
			$arr = getimagesize ( $bgimgname );
			$width = $arr [0];
			$height = $arr [1];
			$str = $arr [2];
			switch ($str) {
				case 1 :
					$bgimg = imagecreatefromgif ( $bgimgname );
					break;
				case 2 :
					$bgimg = imagecreatefromjpeg ( $bgimgname );
					break;
				case 3 :
					$bgimg = imagecreatefrompng ( $bgimgname );
					break;
			}
			imagecopy ( $this->img, $bgimg, 0, 0, 0, 0, $this->width, $this->height ); // 拷贝图像
			imagedestroy ( $bgimg );
		}
	}
	
	// 绘制干扰元素
	private function createObstruct1() {
		$a = $this->width * $this->height / $this->point; // 干扰像素点个数
		
		for($i = 0; $i < $a; $i ++) {
			$color1 = imagecolorclosest ( $this->img, rand ( 0, 255 ), rand ( 0, 255 ), rand ( 0, 255 ) );
			imagesetpixel ( $this->img, rand ( 0, $this->width ), rand ( 0, $this->height ), $color1 );
		}
	}
	
	// 绘制干扰弧线
	private function createObstruct2() {
		$b = round ( $this->width * $this->height / 900 ); // 干扰弧线个数
		for($i = 0; $i < $b; $i ++) {
			$color2 = imagecolorclosest ( $this->img, rand ( 0, 255 ), rand ( 0, 255 ), rand ( 0, 255 ) );
			imagearc ( $this->img, rand ( 0, $this->width ), rand ( 0, $this->height ), rand ( 0, $this->width ), rand ( 0, $this->height ), rand ( 0, 360 ), rand ( 0, 360 ), $color2 );
		}
	}
	
	// 绘制字符
	private function createCode() {
		// 字体大小范围
		$mins = round ( $this->height * 0.65 );
		$maxs = round ( $this->height * 0.75 );
		$mins = round ( $this->height * 0.5 );
		$maxs = round ( $this->height * 0.7 );
		$fontsize = array (); // 每个字符的大小
		$fontfiles = array (); // 每个字符所使用的字体
		$fontrotate = array (); // 每个字符旋转度数
		$fontwidth = array (); // 每个字符串的宽度
		$fontheight = array (); // 每个字符串的高度
		$sum = 0; // 字符串总宽
		          // 随机
		for($i = 0; $i < strlen ( $this->vcode ); $i ++) {
			$fontsize [$i] = round ( rand ( $mins, $maxs ) );
			if (! empty ( $this->fontfiles )) {
				$index = round ( rand ( 0, count ( $this->fontfiles ) - 1 ) );
				$fontfiles [$i] = $this->fontfiles [$index];
				$fontrotate [$i] = round ( rand ( - 20, 20 ) );
				
				$info = imagettfbbox ( $fontsize [$i], $fontrotate [$i], $fontfiles [$i], $this->vcode {$i} );
				$fontwidth [$i] = max ( $info [2] - $info [0], $info [4] - $info [6] );
				$fontheight [$i] = max ( $info [1] - $info [7], $info [3] - $info [5] );
				$sum += $fontwidth [$i];
			} else {
				$fontheight [$i] = $fontsize [$i];
				$fontwidth [$i] = $fontsize [$i];
				$sum += $fontsize [$i];
			}
		}
		$s = $this->rgb [2]; // 随机开始
		$e = $this->rgb [3]; // 随机结束
		$baseX = 0; // 首个字符保留间隔,后面递增
		$diff = 0;
		$fg = 0; // 间隔
		if (! empty ( $this->fontfiles )) {
			$diff = $this->width - $sum - ($fontwidth [0] * 0.2 * 2);
			if ($diff > 0) {
				$fg = $diff / (strlen ( $this->vcode ) + 1);
			}
			$baseX = $fontwidth [0] * 0.2 + $fg;
		} else {
			$diff = $this->width - $sum;
			if ($diff > 0) {
				$fg = $diff / (strlen ( $this->vcode ) + 1);
			}
		}
		for($i = 0; $i < strlen ( $this->vcode ); $i ++) {
			$color = imagecolorallocate ( $this->img, rand ( $s, $e ), rand ( $s, $e ), rand ( $s, $e ) );
			$color = imagecolorallocate ( $this->img, mt_rand ( 0, 255 ), mt_rand ( 0, 255 ), mt_rand ( 0, 255 ) );
			
			// 没有字体文件则使用系统默认
			if (! empty ( $this->fontfiles )) {
				$y = ($this->height - $fontheight [$i]) / 2 + $fontheight [$i];
				imagettftext ( $this->img, $fontsize [$i], $fontrotate [$i], $baseX, $y, $color, $fontfiles [$i], $this->vcode {$i} );
			} else {
				$y = ($this->height - $fontsize [$i]) / 2;
				imagechar ( $this->img, $fontsize [$i], $baseX, $y, $this->vcode {$i}, $color );
			}
			$baseX += $fontwidth [$i] + $fg;
		}
	}
	
	// 生成验证码
	private function makeCode() {
		$strs = $this->code [$this->str];
		for($i = 0; $i < $this->count; $i ++) {
			$this->vcode .= $strs [rand ( 0, strlen ( $strs ) - 1 )];
		}
	}
	
	// 随机颜色,背景为深色则字为浅色否则相反
	private function randColor() {
		$m = rand ( 0, 1 );
		if ($m == 0) {
			$this->rgb [0] = 0;
			$this->rgb [1] = 100;
			$this->rgb [2] = 200;
			$this->rgb [3] = 255;
		} else {
			$this->rgb [0] = 200;
			$this->rgb [1] = 255;
			$this->rgb [2] = 0;
			$this->rgb [3] = 100;
		}
	}
	
	// 输出图像
	private function outputImg() {
		$t = strtolower ( $this->type );
		ob_end_clean (); // 清空头输出
		switch ($t) {
			case "gif" :
				header ( 'Content-Type:image/gif' );
				imagegif ( $this->img );
				break;
			case "jpg" || "jpeg" :
				header ( "Content-Type:image/jpeg" );
				imagejpeg ( $this->img );
				break;
			case "png" :
				header ( "Content-Type:image/png" );
				imagepng ( $this->img );
				break;
		}
		imagedestroy ( $this->img ); // 销毁图像资源
	}
}//class-end