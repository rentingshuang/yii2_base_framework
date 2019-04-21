<?php

/**
 * ���ݿ���¼��ʽ��������ʧ�ܴ���
 * User: shmilyzxt 49783121@qq.com
 * Date: 2016/11/29
 * Time: 14:00
 */
namespace shmilyzxt\queue\failed;

use yii\base\Component;

class DatabaseFailedProvider extends Component implements IFailedProvider
{
    /**
     * ���ݿ�����ʵ��
     * @var \Yii\db\Connection
     */
    public $db;

    /**
     * ��¼������Ϣ�ı�
     * @var string
     */
    public $table = 'failed_jobs';

    public function init()
    {
        parent::init();
        if (!$this->db instanceof \yii\db\Connection && is_array($this->db)) {
            \Yii::$container->setSingleton('failedDb', $this->db);
            $this->db = \Yii::$container->get('failedDb');
        }
    }

    /**
     * ��ʧ����־д�����ݿ�
     */
    public function log($connector, $queue, $payload)
    {
        return $this->db->createCommand()->insert($this->table, [
            'connector' => $connector,
            'queue' => $queue,
            'payload' => $payload,
            'failed_at' => date("Y-m-d H:i:s", time())
        ])->execute();
    }
}