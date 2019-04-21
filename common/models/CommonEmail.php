<?php

namespace common\models;

use Yii;
use yii\base\Model;
use common\extend\mail\PHPMailer;
use common\components\CommonFun;

class CommonEmail extends Model {
	
	/**
	 * 发送电子邮件
	 * @author RTS 2016年10月19日 16:47:02
	 * @param string $data 邮件数据集合
	 */
	public static function send($data = []) {
		if(empty($data)){
			return false;
		}
		$toAddress = CommonFun::getArrayValue($data,'address','');
		if(empty($data)){
			return false;
		}
		$toAddress = explode(',', $toAddress);
		
		$subject = CommonFun::getArrayValue($data,'subject','subject');
		$content = CommonFun::getArrayValue($data,'content','content');
		$fromName = CommonFun::getArrayValue($data,'fromName','人人快递');

		$mail = new PHPMailer(true); 
		$mail->IsSMTP();
		try {
			$mailer = Yii::$app->params['Mailer'];
			$mail->SMTPDebug  = 0; 
			$mail->SMTPAuth   = true;
			$mail->Host       = "smtp.exmail.qq.com";
			$mail->Port       = 25;
			$mail->Username   = $mailer['username'];
			$mail->Password   = $mailer['password'];
			
			$from = $mail->Username;
			$mail->AddReplyTo($from, $fromName);
			
			foreach ($toAddress as $address){
				if(empty($address)){
					continue;
				}
				$mail->AddAddress($address, '');
			}
			
			$mail->SetFrom($from, $fromName);
			$mail->AddReplyTo($from, $fromName);
			$mail->Subject = $subject;
			//$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
			$mail->MsgHTML($content);
			$mail->Send();
			return true;
		} catch (phpmailerException $e) {
			return $e->errorMessage(); 
		} catch (\Exception $e) {
			return $e->getMessage(); 
		}
	}
}
