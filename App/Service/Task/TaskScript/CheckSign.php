<?php

namespace App\Service\Task\TaskScript;


use Core\Model;

class CheckSign extends ScriptBase implements ScriptInterface
{

    /**
     * 检查签到任务
     *
     * @param array $task
     * @param int $uid
     *
     * @return mixed
     */
    public function check($task, $uid)
    {
        $this->task = $task;
        $this->uid = $uid;

        /**
         * 签到任务是否以前签过到了
         */
        $model = new Model();
        $check_sign = $model->find('select * from video_user_check_sign where uid=:uid',array('uid'=>$uid));

        if (!$check_sign) {
            return 'can_apply';
        }

        if ($task['relatedid']) {
            $isDo = $model->find('select * from video_task_user where relatedid=? and uid=?', array($task['relatedid'], $user['uid']));
            // 当没有申请或者没有完成父任务时
            if (!$isDo || $isDo['status'] != 1) {
                return 'can_apply';
            }
        }

        /**
         * 计算差值
         */
        $s = date('Ymd',time())-date('Ymd',strtotime($check_sign['last_time']));
        if($s==0){
            return 'all';
        }

        if($s >= 1){
            return 'can_apply';
        }

    }

    public function checkCsc()
    {

    }
}