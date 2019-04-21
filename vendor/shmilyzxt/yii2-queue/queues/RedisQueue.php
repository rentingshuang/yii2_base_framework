<?php
/**
 * redis����
 * push��ͨ��redis�б�ʵ�ֶ���
 * later��ͨ��redis���򼯺�ʵ�ֶ���
 * User: shmilyzxt 49783121@qq.com
 * Date: 2016/11/23
 * Time: 17:13
 */

namespace shmilyzxt\queue\queues;

use shmilyzxt\queue\base\Queue;
use shmilyzxt\queue\helper\ArrayHelper;

class RedisQueue extends Queue
{
    /**
     * predis����ʵ��
     * @var \Predis\Client
     */
    public $connector;

    public function init()
    {
        parent::init();

        if (!class_exists('\Predis\Client')) {
            throw new \Exception('the extension predis\predis does not exist ,you need it to operate redis ,you can run "composer require predis/predis" to gei it!');
        }

        if (!$this->connector instanceof \Predis\Client) {
            \Yii::$container->setSingleton('connector', $this->connector);
            $this->connector = \Yii::$container->get("connector")->connect();
        }
    }

    /**
     * �����
     * @param $job
     * @param string $data
     * @param null $queue
     * @return int
     */
    protected function push($job, $data = '', $queue = null)
    {
        return $this->connector->rpush($this->getQueue($queue), $this->createPayload($job, $data, $queue));
    }

    /**
     * ��ʱ���������
     * @param $dealy
     * @param $job
     * @param string $data
     * @param null $queue
     * @return int
     */
    protected function later($dealy, $job, $data = '', $queue = null)
    {
        return $this->connector->zadd($this->getQueue($queue) . ':delayed', time() + $dealy, $this->createPayload($job, $data, $queue));
    }

    /**
     * ������
     * @param null $queue
     * @return object
     * @throws \yii\base\InvalidConfigException
     */
    public function pop($queue = null)
    {
        $original = $queue ?: $this->queue;
        $queue = $this->getQueue($queue);

        if (!is_null($this->expire)) {
            $this->migrateAllExpiredJobs($queue);
        }

        $job = $this->connector->lpop($queue);

        if (!is_null($job)) {
            $this->connector->zadd($queue . ':reserved', time() + $this->expire, $job);

            $config = array_merge($this->jobEvent, [
                'class' => 'shmilyzxt\queue\jobs\RedisJob',
                'queue' => $original,
                'job' => $job,
                'queueInstance' => $this,
            ]);

            return \Yii::createObject($config);
        }

        return false;
    }

    /**
     * ��ȡ���е�ǰ������ = ִ�ж��������� + �ȴ�����������
     * @param null $queue
     * @return mixed
     */
    public function getJobCount($queue = null)
    {
        $queue = $this->getQueue($queue);
        return $this->connector->llen($queue) + $this->connector->zcard($queue . ":delayed");
    }

    /**
     * ���������¼��������
     * ��ʱ������ĳ��Դ���Ҫ��1
     * @param  string $queue
     * @param  string $payload
     * @param  int $delay
     * @param  int $attempts
     * @return void
     */
    public function release($queue, $payload, $delay, $attempts = 0)
    {
        $payload = $this->setMeta($payload, 'attempts', $attempts);
        $this->connector->zadd($this->getQueue($queue) . ':delayed', time() + $delay, $payload);
    }

    /**
     * �������������id��attempts�ֶ�
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = parent::createPayload($job, $data);
        $payload = $this->setMeta($payload, 'id', $this->getRandomId());
        return $this->setMeta($payload, 'attempts', 1);
    }

    /**
     * ����һ���������Ϊid
     * @param int $length
     * @return string
     */
    protected function getRandomId()
    {
        $string = md5(time() . rand(1000, 9999));
        return $string;
    }

    /**
     * ��ȡ�������ƣ���redis�����key��
     * @param  string|null $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return 'queues:' . ($queue ?: $this->queue);
    }

    /**
     * ����ʱ���񵽴�ִ��ʱ��ʱ������ʱ�������ʱ���񼯺����ƶ�����ִ�ж�����
     * @param  string $from
     * @param  string $to
     * @return void
     */
    public function migrateExpiredJobs($from, $to)
    {
        $options = ['cas' => true, 'watch' => $from, 'retry' => 10];
        $this->connector->transaction($options, function ($transaction) use ($from, $to) {
            //������Ҫ��ȡ��ʱ������������Ѿ���ִ��ʱ�������Ȼ�����Щ����ת�Ƶ���ִ�ж����б��У�����ʹ����redis����
            $jobs = $this->getExpiredJobs(
                $transaction, $from, $time = time()
            );

            if (count($jobs) > 0) {
                $this->removeExpiredJobs($transaction, $from, $time);
                $this->pushExpiredJobsOntoNewQueue($transaction, $to, $jobs);
            }
        });
    }

    /**
     * ���Ѵ�������ɾ��һ������
     * @param  string $queue
     * @param  string $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->connector->zrem($this->getQueue($queue) . ':reserved', $job);
    }

    /**
     * ��ָ������ɾ����������
     * @param  \Predis\Transaction\MultiExec $transaction
     * @param  string $from
     * @param  int $time
     * @return void
     */
    protected function removeExpiredJobs($transaction, $from, $time)
    {
        $transaction->multi();
        $transaction->zremrangebyscore($from, '-inf', $time);
    }

    /**
     * �������һ�������ƶ�����һ������
     * @param  \Predis\Transaction\MultiExec $transaction
     * @param  string $to
     * @param  array $jobs
     * @return void
     */
    protected function pushExpiredJobsOntoNewQueue($transaction, $to, $jobs)
    {
        call_user_func_array([$transaction, 'rpush'], array_merge([$to], $jobs));
    }

    /**
     * �ϲ��ȴ�ִ�к��Ѿ����������
     * @param  string $queue
     * @return void
     */
    protected function migrateAllExpiredJobs($queue)
    {
        $this->migrateExpiredJobs($queue . ':delayed', $queue);
        $this->migrateExpiredJobs($queue . ':reserved', $queue);
    }

    /**
     * ����������������µ��ֶ�
     * @param  string $payload
     * @param  string $key
     * @param  string $value
     * @return string
     */
    protected function setMeta($payload, $key, $value)
    {
        $payload = unserialize($payload);
        $newPayload = serialize(ArrayHelper::set($payload, $key, $value));
        return $newPayload;
    }

    /**
     * ��ָ�������л�ȡ���г�ʱ������
     * @param  \Predis\Transaction\MultiExec $transaction
     * @param  string $from
     * @param  int $time
     * @return array
     */
    protected function getExpiredJobs($transaction, $from, $time)
    {
        return $transaction->zrangebyscore($from, '-inf', $time);
    }

    /**
     * ���ָ������
     * @param null $queue
     * @return integer
     * @throws \Exception execution failed
     */
    public function flush($queue = null)
    {
        $queue = $this->getQueue($queue);
        return $this->connector->del([$queue, $queue . ":delayed", $queue . ":reserved"]);

    }
}