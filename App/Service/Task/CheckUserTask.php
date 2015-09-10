<?php

namespace App\Service\Task;

use App\Service\Task\TaskScript\ScriptBase;
use Core\Model;

class CheckUserTask
{
    protected $user;
    protected $task;
    protected $em;

    /**
     * 任务脚本的对应枚举
     * key 取之于task表中的script_name
     * @var array
     */
    protected $script = array(
        'check_email'=>'CheckEmail',
        'points'=>'Charge',
        'check_in'=>'CheckSign',
        'invite'=>'Invite'
    );

    /**
     * 初始化方法 注入了doctrine的manager
     */
    public function __construct()
    {
    }

    /**
     * 检测用户任务对应的状态的问题
     * @param $task  array 某条任务
     * @param $uid  int 某个用户
     *
     * @return mixed
     * <p>
     *  返回 FALSE 则表示任务失败
     *  返回 TRUE 则表示任务用户可以做
     *  返回 array进度的数组 则根据情况更新
     * </p>
     */
    public function checkTask($task, $uid)
    {
        $this->task = $task;
        $this->user = $uid;

        /**
         * 当为period 为 0 一次性的任务的时候 判断是否已经完成
         * 后面各种任务脚本中就不用再判断此任务了
         */
        if($task['period']== 0){

            $model = new Model();
            $taskUser= $model->find('select * from video_task_user where vtask_id='.$task['vtask_id'].' and uid='.$uid);
            // 当task_user 的status 为 1的时候就表示完成且领奖了
            if($taskUser && $taskUser['status'] == 1){
                return 'all';
            }
        }

        /**
         * 进行检查和更新各个任务的状态 调用各任务脚本
         */
        $script = $task['script_name'];
        $className = 'App\Service\Task\TaskScript\\'.ucfirst($this->script[$script]);
       // 暂时做了doctrine($this->em)的手动注入 未走service
        $class = new $className();
        return $class->check($task,$uid);
    }

    /**
     * 结算送礼物
     *
     * @param $task
     * @param $uid
     */
    public function doBillGift($task,$uid)
    {
        $class  = new ScriptBase();
        $result  = $class->billGift($task,$uid);
        return $result;
    }

}
