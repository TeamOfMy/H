<?php

namespace App\Service\Task\TaskScript;


interface ScriptInterface
{


    /**
     * 检查每个任务用户的状态已经是否可以接任务
     * @param $task
     * @param $uid
     * @return mixed
     * <p>
     *  success 任务成功了 等待领取奖励
     *  all  表示即完成又领取了奖励
     *  can_apply 表示可以申请任务
     *  doing 申请了且还没完成中
     *  no_apply 没不能申请
     *  failed 失败的 比如没在规定时间内完成的
     * </p>
     */
    public function check( $task, $uid);

    public function checkCsc();
}