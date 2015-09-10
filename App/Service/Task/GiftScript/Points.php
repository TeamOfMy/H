<?php

namespace App\Service\Task\GiftScript;

use Core\Model;

class Points extends GiftBase implements GiftInterface
{

    /**
     * 送钻石
     *
     * @param $gift
     * @param $uid
     * @return mixed
     */
    public function present($gift, $uid)
    {
        $model = new Model();
        $this->setRedis();
        $userinfo = $this->_redisInstance->hGetAll('huser_info:' . $uid);

        $points = $userinfo['points'] + $gift;
        $result = $model->flush('video_user', array('points' => $points), array('uid' => $uid));
        if ($result !== false) {
            $this->_redisInstance->hset('huser_info:' . $uid, 'points', $points);
            return true;
        }
        return false;
    }

}