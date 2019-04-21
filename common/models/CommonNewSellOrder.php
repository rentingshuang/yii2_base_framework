<?php

namespace common\models;

use Yii;

use yii\db\ActiveRecord;


/**
 * ContactForm is the model behind the contact form.
 */
class CommonNewSellOrder extends ActiveRecord {
	
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'business_new_sell_order';
	}

}
