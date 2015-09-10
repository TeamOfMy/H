<?php
namespace App\Service\Task\GiftScript;

use Core\Model;

class GiftBase
{

    protected $_redisInstance;
    protected $config;

    /**
     * 设置redis
     */
    protected function  setRedis()
    {
        $this->_redisInstance = new \Redis();
        $model = new Model();
        $this->config = $model->_confAssoc;
        $redis_ip_port = $this->config['REDIS_CLI_IP_PORT'];
        $redis_ip_port = explode(':', $redis_ip_port);
        $this->_redisInstance->connect($redis_ip_port[0], $redis_ip_port[1]);
        // $this->_redisInstance = $this->get('snc_redis.cache');
    }

    /**
     * 获取等级对应的经验的值
     *
     * @return array
     */
    protected function getLvRich()
    {
        $model = new Model();
        $lvs = $model->findAll('select * from video_level_rich');
        $data = array();
        foreach($lvs as $lv){
            $data[$lv['level_id']] = $lv;
        }
        return $data;
    }

    /**
     * 回收redis的链接
     */
    public function __destruct()
    {
        if( $this->_redisInstance != null ){
            $this->_redisInstance->close();
        }
    }
}