<?php

namespace App\Service\Task\TaskScript;

use App\Service\Task\GiftScript\Top;
use App\Service\Task\GiftScript\Goods;
use App\Service\Task\GiftScript\Icon;
use App\Service\Task\GiftScript\Medals;
use App\Service\Task\GiftScript\Points;
use App\Service\Task\GiftScript\Level;
use Core\Model;

class ScriptBase
{
    protected $task;
    protected $uid;
    protected $gift_script = array();
    protected $lv_rich = array(
        1 => '',
        2 => '一富',
        3 => '二富',
        4 => '三富',
        5 => '四富',
        6 => '五富',
        7 => '六富',
        8 => '七富',
        9 => '八富',
        10 => '九富',
        11 => '十富',
        12 => '男爵',
        13 => '子爵',
        14 => '伯爵',
        15 => '侯爵',
        16 => '公爵',
        17 => '郡公',
        18 => '国公',
        19 => '王爵',
        20 => '藩王',
        21 => '郡王',
        22 => '亲王',
        23 => '国王',
        24 => '帝王',
        25 => '皇帝',
        26 => '天君',
        27 => '帝君',
        28 => '圣君',
        29 => '主君',
        30 => '先君',
        31 => '神'
    );

    public function __construct()
    {

    }

    /**
     * 任务完成后礼物的分配
     *
     * @param $task
     * @param $uid
     */
    public function billGift($task, $uid)
    {
        $this->initGiftScript();
        $gift = $task['bonus'];

        $msg_content = '恭喜你完成' . $task['task_name'] . '任务,获得了';
        /**
         * flag 是用于判断所有礼物是否都发放成功了
         */
        $flag = false;
        if (empty($gift)) {
            $flag = true;
        }else {
            foreach ($gift as $key => $value) {
                if (isset($this->gift_script[$key])) {
                    /**
                     * 根据不同礼物类型，调用不同的脚本进行礼物的发送
                     */
                    $flag = $this->gift_script[$key]->present($value, $uid);
                } else {
                    continue;
                }

                if ($key == 'points') {
                    $msg_content .= $value . '个钻石';
                    // 记录钻石日志
                    $this->updateRecharge($uid, $value);
                }
                if ($key == 'top') {
                    $msg_content .= '升到了' . $this->lv_rich[$value];
                }
                if ($key == 'goods' || $key == 'icon') {
                    $msg_content .= '';
                    foreach ($value as $v) {
                        if (isset($v['num'])) {
                            $msg_content .= $v['num'];
                        }
                        $msg_content .= $v['name'];
                        if (isset($v['exp']) && $v['exp']) {
                            $msg_content .= $v['exp'] . '天';
                        }
                    }

                }
            }
        }
        $msg_content .= '奖励。';
        // 发送消息
        $this->sendMsg($uid, $msg_content);

        // 更新状态为status=1  表示奖励已经领取了
        $this->flushResult($task,$uid);

        // 更新完成的人数 增加1
        $this->updateTaskAchievers($task);
        return $flag;

    }

    /**
     * 更新状态为 all status=1 表示全部完成
     *
     * @param $task
     * @param $uid
     */
    protected function flushResult($task,$uid)
    {
        $model = new Model();
        $userTask= $model->find('select * from video_task_user where vtask_id='.$task['vtask_id'].' and uid='.$uid);
        $model->flush('video_task_user',array('status'=>1),array('auto_id'=>$userTask['auto_id']));
    }

    /**
     * 初始化各种礼物的对象
     */
    protected function initGiftScript()
    {
        $this->gift_script = array(
            // 商品
            'goods' => new Goods(),

            'icon' => new Icon(),
            // 送钻石
            'points' => new Points(),
            // 勋章
            'medals' => new Medals(),
            // 等级直达多少级
            'top' => new Top(),
            // 提升多少等级
            'level' => new Level()
        );
    }

    /**
     * 记录送钻石的日志
     * @param $uid int 用id
     * @param $points int 要增加的钱
     */
    protected function updateRecharge($uid, $points)
    {
        $model = new Model();
        $user = $model->find('select * from video_user where uid=' . $uid);
        $arr = array(
            'uid' => $uid,
            'created' => date('Y-m-d H:i:s'),
            'points' => $points,
            'order_id' => time(),
            'pay_type' => 5,//服务器送的钱pay_type=5
            'pay_id' => null,
            'nickname' => $user['nickname']
        );

        $model = new Model();
        $model->add('video_recharge', $arr);
    }

    protected function delTaskRedis()
    {
        $redis = new \Redis();
        $model = new Model();
        $config = $model->_confAssoc;
        $redis_ip_port = $config['REDIS_CLI_IP_PORT'];
        $redis_ip_port = explode(':', $redis_ip_port);
        $redis->connect($redis_ip_port[0], $redis_ip_port[1]);
        $task = $redis->del('alltask');
    }

    /**
     * 增加申请人数
     * @param $task
     */
    protected function updateTaskApplicants($task)
    {
        $model = new Model();
        $model->flush('video_task',array('applicants'=>$task['applicants']+1),array('vtask_id'=>$task['vtask_id']));
        $this->delTaskRedis();
    }

    /**
     * 增加完成人数
     * @param $task
     */
    protected function updateTaskAchievers($task)
    {
        $model = new Model();
        $model->flush('video_task',array('achievers'=>$task['achievers']+1),array('vtask_id'=>$task['vtask_id']));
        $this->delTaskRedis();
    }

    /**
     * 发送提示消息
     */
    protected function sendMsg($uid, $content)
    {
        $arr = array(
            'send_uid' => 0,
            'rec_uid' => $uid,
            'content' => $content,
            'category' => 1,
            'status' => 0,
            'created' => date('Y-m-d H:i:s'),
        );

        $model = new Model();
        $model->add('video_mail', $arr);
    }


}