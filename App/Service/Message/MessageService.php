<?php

namespace App\Service\Message;

use App\Models\Messages;
use Core\Service;

class MessageService extends Service
{

    /**
     * 发送一个消息 一对一
     */
    public function sendMessage()
    {


    }

    /**
     * 根据用户id获取对应的数据
     *
     * @param $uid int 用id
     * @param int $num 分页的条数
     * @return mixed
     */
    public function getMessageByUid($uid,$num=10)
    {
        $msg = Messages::where('rec_uid',$uid)->orderBy('id','desc')->paginate($num);
        return $msg;
    }

    /**
     * 根据用户id 和 消息类型 获取对应的数据
     *
     * @param $uid int 用id
     * @param $type int 消息类型id 默认2为用户私信
     * @param int $num 分页的条数
     * @return mixed
     */
    public function getMessageByUidAndType($uid,$type=2,$num=10)
    {
        $msg = Messages::where('rec_uid',$uid)->where('category',$type)
            ->orderBy('id','desc')->paginate($num);
        return $msg;
    }
}