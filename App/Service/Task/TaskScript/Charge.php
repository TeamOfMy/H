<?php

namespace App\Service\Task\TaskScript;
use Core\Model;


class Charge extends ScriptBase implements ScriptInterface
{


    /**
     * 检查首充的任务
     *
     * @param $task
     * @param $uid
     *
     * @return mixed
     */
    public function check($task, $uid)
    {
        $this->task = $task;
        $this->uid = $uid;

        $model = new Model();
        $userTask= $model->find('select * from video_task_user where vtask_id='.$task['vtask_id'].' and uid='.$uid);
        $user = $model->find('select * from video_user where uid='.$uid);

        if ($task['pre_vtask_id']) {
            $isDo = $model->find('select * from video_task_user where vtask_id=? and uid=?',array($task['pre_vtask_id'],$user['uid']));
            // 当没有申请或者没有完成父任务时
            if (!$isDo || $isDo['status'] != 1) {
                return 'can_apply';
            }
        }
        /**
         * 如果没有接任务的话，就判断是否可以自动申请任务
         */
        if (!$userTask) {

            /**
             * 只要用户满足做这个任务的条件就要插入一条数据 等待充值的验证
             */
            $c = serialize(array('csc'=>0,'update_time'=>time()));
            $data = array(
                'uid'=>$uid,
                'vtask_id'=>$task['vtask_id'],
                'status'=>0,
                'csc'=>$c,
                'apply_date'=>time(),
                'init_time'=>date('Y-m-d H:i:s'),
                'dml_time'=>date('Y-m-d H:i:s'),
                'dml_flag'=>1
            );
            $model->add('video_task_user',$data);
            // 增加一个申请人数
            $this->updateTaskApplicants($task);
            return 'doing';
        }

        $ut = $userTask;
        $csc = unserialize($ut['csc']);
        if ($ut['status'] == 1) {
            return 'all';
        }

        if ($ut['status'] == -1) {
            return 'failed';
        }

        if ($ut['status'] == 0 && $csc['csc'] == 100) {

            return 'success';
        }

        /**
         * 当没有完成的时候要检查进度
         */
        if ($ut['status'] == 0 && $csc['csc'] != 100) {
            /**
             * 检查是否完成了充值了，是就更新状态
             * 要求在申请任务之后 充值的才算
             * 充值的大小是限制的
             */
            $xianzhi = $task['points']['value'];
            $charge = $model->find('select * from video_charge_list where uid=? and ttime>? and paymoney>=? and paymoney<=? and status=?',
                    array($uid,date('Y-m-d H:i:s',$ut['apply_date']),$xianzhi[0],$xianzhi[1],2)
                );
            if($charge){
                $data = array();
                $csc['csc'] = 100;
                $csc['update_time'] =time();
                $data['csc'] = serialize($csc);
                $model->flush('video_task_user',$data,array('auto_id'=>$ut['auto_id']));
                return 'success';
            }
            return 'doing';
        }
    }

    public function checkCsc()
    {

    }
}