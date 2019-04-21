<?php

namespace common\components;

use Yii;
use yii\helpers\StringHelper;

class CommonToken  {
	
	const CSRF_MASK_LENGTH = 10;
	
	public function get(){
		$token = Yii::$app->getSecurity()->generateRandomString();
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.';
		$mask = substr(str_shuffle(str_repeat($chars, 5)), 0, static::CSRF_MASK_LENGTH);
		// The + sign may be decoded as blank space later, which will fail the validation
		
		$_token = str_replace('+', '.', base64_encode($mask . $this->xorTokens($token, $mask)));
		echo '原始：'.$token.'</br>';
		echo '混淆后输出给用户：'.$_token.'</br>';
		$res = $this->validate($_token,$token);
		echo '传入校验参数：$_token：'.$_token,',结果：'.$res;
	}
	
	public function validate($token, $trueToken)
	{
		if (!is_string($token)) {
			return false;
		}
		$token = base64_decode(str_replace('.', '+', $token));
	
		$n = StringHelper::byteLength($token);
		if ($n <= static::CSRF_MASK_LENGTH) {
			return false;
		}
		$mask = StringHelper::byteSubstr($token, 0, static::CSRF_MASK_LENGTH);
		$token = StringHelper::byteSubstr($token, static::CSRF_MASK_LENGTH, $n - static::CSRF_MASK_LENGTH);
		$token = $this->xorTokens($mask, $token);
		return $token === $trueToken;
	}
	
	private function xorTokens($token1, $token2)
	{
		$n1 = StringHelper::byteLength($token1);
		$n2 = StringHelper::byteLength($token2);
		if ($n1 > $n2) {
			$token2 = str_pad($token2, $n1, $token2);
		} elseif ($n1 < $n2) {
			$token1 = str_pad($token1, $n2, $n1 === 0 ? ' ' : $token1);
		}
	
		return $token1 ^ $token2;
	}
}