<?php

namespace App\Service\Task\GiftScript;

use  Core\Model;
class Icon extends GiftBase implements GiftInterface
{
    /**
     * 添加用户
     *
     * @param $gifts
     * @param $uid
     */
    public function present($gifts,$uid)
    {

        $model = new Model();
        $gift = $gifts[0];
        $data = array(
            'icon_id'=> $gift['id']
        );
        $result = $model->flush('video_user',$data,array('uid'=>$uid));
        if($result !== false){
            $this->setRedis();
            $this->_redisInstance->hset('huser_info:'.$uid,'icon_id',$gift['id']);
            return true;
        }
        return false;
    }
}