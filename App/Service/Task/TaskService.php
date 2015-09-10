<?php
namespace App\Service\Task;

use Core\Model;

class TaskService
{
    protected $uid;
    protected $uR;
    protected $em;
    protected $user;

    /**
     * 系统中其他地方定义的任务触发的标志位和任务脚本的关系
     * example :
     *      一个充值动作就有两类任务触发 充值 和 首充
     *      charge=> 'points,first_points'
     *
     * @var array
     */
    protected $key_script_index = array(
        'charge' => 'points'
    );

    public function __construct($uid = '')
    {
        $this->uid = $uid;
    }

    /**
     * 调用时要初始化用户的对象
     *
     * @param $uid int 用户的uid
     *
     * @return $this object
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * 获取所有的上线的任务
     *
     * @return mixed
     */
    public function getAllTask()
    {

        $model = new Model();
//        $list = $model->findAll('select * from video_task  where status=?  and dml_flag !=3', array('0'));
        $list = $this->getTaskFromRedis();
        /**
         * 判断是否有特殊的扩展变量
         */
        $tid = array();
        foreach ($list as $ke => $li) {
            // 格式化礼物
            if (!empty($li['bonus'])) {
                $list[$ke]['bonus'] = $this->formatGift($li['bonus']);
            } else {
                $list[$ke]['bonus'] = '';
            }
            $tid[] = $li['vtask_id'];
        }
        $var = $model->findAll('select * from video_task_conf');
        if (!empty($tid)) {
            foreach ($list as $key => $li) {
                foreach ($var as $value) {
                    if ($value['vtask_id'] == $li['vtask_id']) {
                        if ($value['variable'] == 'points') {
                            $value['value'] = unserialize($value['value']);
                        }
                        $list[$key][$value['variable']] = $value;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 根据redis中获取所有的任务数据 TODO 临时放这儿
     */
    protected function  getTaskFromRedis()
    {
        $redis = new \Redis();
        $model = new Model();
        $config = $model->_confAssoc;
        $redis_ip_port = $config['REDIS_CLI_IP_PORT'];
        $redis_ip_port = explode(':', $redis_ip_port);
        $redis->connect($redis_ip_port[0], $redis_ip_port[1]);
        $task = $redis->get('alltask');
        if($task){
            return json_decode($task,true);
        }else {
            $list = $model->findAll('select * from video_task  where status=?  and dml_flag !=3', array('0'));
            $redis->set('alltask',json_encode($list));
            return $list;
        }
    }


    /**
     * 获取系统的任务 且 标记用户个任务的状态
     *
     * <p>
     *  获取的过程中会对用户的各个任务的状态进行检查和更新
     *  有些任务类别中可能会自动为用户插入task_user 开启任务
     *  其中有些任务判断是多次的，都是在各自的脚本中处理的
     * </p>
     *
     * @return array[object] 上线的任务
     */
    public function getAllUserCanTask()
    {
        $tasks = $this->getAllTask();
        // 任务进度检查服务
        $check_task = new CheckUserTask();
        foreach ($tasks as $key => $task) {
            // 去检查每一个任务用户对应的状态
            $result = $check_task->checkTask($task, $this->uid);
            $tasks[$key]['userStatus'] = $result;
        }
        return $tasks;
    }

    /**
     * 格式化 礼物显示的样式
     *
     * @param $bonus
     * @return array
     */
    protected function formatGift($bonus)
    {
        if (empty($bonus)) {
            return array();
        }

        $bonus = unserialize($bonus);
        $model = new Model();

        foreach ($bonus as $key => &$value) {
            if (empty($value)) {
                return array();
            }
            if ($key == 'goods' || $key == 'medals' || $key == 'icon') {
                foreach ($value as &$v) {
                    $goods = $model->find('select * from video_goods where gid=?', array($v['id']));
                    if($goods) {
                        $v['name'] = $goods['name'];
                    }
                }
            }
        }
        return $bonus;
    }

    /**
     * 结算用的任务 领取奖励
     *
     * @param $task_id
     * @return mixed
     */
    public function billTask($task_id)
    {

        /**
         * 获取任务详情
         */
        $model = new Model();
        $task = $model->find('select * from video_task  where vtask_id=? and status=? and dml_flag !=3', array($task_id, '0'));

        if (!$task) {
            return false;
        }

        // 格式化礼物
        if (!empty($task['bonus'])) {
            $task['bonus'] = $this->formatGift($task['bonus']);
        } else {
            $task['bonus'] = '';
        }

        /**
         * 判断是否有特殊的扩展变量
         */
        $var = $model->findAll('select * from video_task_conf where vtask_id=?', array($task_id));
        if ($var) {
            foreach ($var as $value) {
                if ($value['variable'] == 'points') {
                    $value['value'] = unserialize($value['value']);
                }
                $task[$value['variable']] = $value;
            }
        }
        $check_task = new CheckUserTask();
        // 去检查每一个任务用户对应的状态
        $result = $check_task->checkTask($task, $this->uid);
        if($result == 'success'){
            $flag = $check_task->doBillGift($task,$this->uid);
        }else {
            $flag = false;
        }
        return $flag;
    }
}