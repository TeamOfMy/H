<?php

namespace App\Service\Task\TaskScript;

use Core\Model;

class CheckEmail extends ScriptBase implements ScriptInterface
{

    protected $stat_url = array(

    );

    /**
     * 检测邮箱任务的完成程度
     *
     * 邮箱如果验证了，就标示邮箱任务已经完成
     * 如果在任务上线系统之前验证过的，就不用了
     * 如果还没验证，就启动任务，并送礼
     * @param $task
     * @param $uid
     *
     * @return mixed
     *
     */
    public function check($task,$uid)
    {
        $this->task = $task;
        $this->uid = $uid;

        /**
         * 获取用户对应的task_user的数据
         * 如果已经验证了，但是又没有为这个用户插入这个任务的话
         * 即在 任务系统上线前 就已经认证的用户 此时要插入一条完成了任务的数据
         */

        $model = new Model();
        $userTask= $model->find('select * from video_task_user where vtask_id='.$task['vtask_id'].' and uid='.$uid);
        $user = $model->find('select * from video_user where uid='.$uid);
        if ($user['safemail'] && !$userTask) {
            $c = serialize(array('csc'=>100,'update_time'=>time()));
            $data = array(
                'uid'=>$uid,
                'vtask_id'=>$task['vtask_id'],
                'status'=>1,
                'csc'=>$c,
                'apply_date'=>time(),
                'init_time'=>date('Y-m-d H:i:s'),
//                'dml_time'=>date('Y-m-d H:i:s'),
                'dml_flag'=>1
            );
            $model->add('video_task_user',$data);

            // 当完成后的加上1个完成用户
            $this->updateTaskAchievers($task);
            return 'all';
        }

        /**
         * 既没验证 又没申请任务的
         * 做自动申请操作 等待邮箱的安全验证
         * 就返回false
         *
         */
        if (!$user['safemail'] && !$userTask) {
            $c = serialize(array('csc'=>0,'update_time'=>time()));
            $data = array(
                'uid'=>$uid,
                'vtask_id'=>$task['vtask_id'],
                'status'=>0,
                'csc'=>$c,
                'apply_date'=>time(),
                'init_time'=>date('Y-m-d H:i:s'),
//                'dml_time'=>date('Y-m-d H:i:s'),
                'dml_flag'=>1
            );
            $model->add('video_task_user',$data);
            // 增加一个申请人数
            $this->updateTaskApplicants($task);
            return 'doing';
        }

        /**
         * 完成且领取奖励
         */
        if($userTask['status'] == 1){
            return 'all';
        }
        /**
         * 失败的
         */
        if($userTask['status'] == -1){
            return 'failed';
        }

        /**
         * 完成了，且等待领取奖励
         */
        $csc = unserialize($userTask['csc']);
        if ($csc['csc'] == 100 && $userTask['status'] == 0) {
            return 'success';
        }

        /**
         * 当进度不是满的，此时要检查下完成情况
         * 进行更新与否的判断和操作
         * 如果用户已经完成了邮箱的验证 就更新状态
         */
        if($csc['csc'] != 100){
            if($user['safemail']){
                $csc['csc'] = 100;
                $csc['update_time'] = time();
                $model->flush('video_task_user',array('csc'=>serialize($csc)),array('auto_id'=>$userTask['auto_id']));
                return 'success';
            }
            return 'doing';
        }
    }

    public function checkCsc()
    {

    }

}