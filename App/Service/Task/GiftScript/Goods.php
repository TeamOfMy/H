<?php

namespace App\Service\Task\GiftScript;

use Core\Model;
class Goods extends GiftBase implements GiftInterface
{

    /**
     * 添加用户的礼物到背包中
     *
     * @param $gifts
     * @param $uid
     */
    public function present($gifts,$uid)
    {
        $model = new Model();
        $flag = false;
        foreach($gifts as $gift) {
            $has_gif = $model->find('select * from video_pack where uid=? and gid=?', array($uid, $gift['id']));
            if ($has_gif) {
                if (!empty($gift['exp'])) {
                    $expires = $has_gif ['expires'] + $gift['exp'] * 24 * 3600;
                    $model->flush('video_pack', array('expires' => $expires), array('uid' => $uid, 'gid' => $gift['id']));
                }
                $flag = true;
            }else {
                $exp = time() + $gift['exp'] * 24 * 3600;
                $result = $model->add('video_pack', array('uid' => $uid, 'gid' => $gift['id'], 'num' => $gift['num'], 'expires' => $exp));
                if ($result !== false) {
                    $flag = true;
                } else {
                    $flag = false;
                }
            }
        }
        return $flag;
    }
}