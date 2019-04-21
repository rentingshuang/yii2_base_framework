<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class CommonBusinessStatisticsDetails extends ActiveRecord{
	
	public static function tableName() {
		return 'business_statistics_details';
	}
	
}

