<?php
/**
 * Beanstalkd ��������.
 * User: shmilyzxt 49783121@qq.com
 * Date: 2016/11/28
 * Time: 14:15
 */

namespace shmilyzxt\queue\jobs;

use shmilyzxt\queue\base\Job;

class BeanstalkdJob extends Job
{
    /**
     * @var \Pheanstalk\Pheanstalk
     */
    public $pheanstalk;

    /**
     * @var \Pheanstalk\Job
     */
    public $job;

    public function init()
    {
        parent::init();
        $this->pheanstalk = $this->queueInstance->connector;
    }


    /**
     * ��ȡ�����Դ���
     * @return int
     */
    public function getAttempts()
    {
        $stats = $this->pheanstalk->statsJob($this->job);
        return (int)$stats->reserves;
    }

    /**
     * ��ȡ��������
     * @return string
     */
    public function getPayload()
    {
        return $this->job->getData();
    }

    /**
     * ɾ��һ������
     * @return void
     */
    public function delete()
    {
        parent::delete();
        $this->pheanstalk->delete($this->job);
    }

    /**
     * ���������¼������
     * @param  int $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
        $this->queueInstance->release($this->queue, $this->job, $delay);
    }

    /**
     * ����һ������beanstalkd���й��ܣ�
     * @return void
     */
    public function bury()
    {
        $this->pheanstalk->bury($this->job);
    }

    /**
     * Get the job identifier.
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getId();
    }
}