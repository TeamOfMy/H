<?php

namespace App\Controller;

use App\Models\Users;
use App\Service\Task\TaskService;
use Core\Model;
use Core\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaskController extends BaseController
{

    /**
     * 获取一个登陆用户的所有的可以做的项目的
     *
     * @return Response
     */
    public function index()
    {

        $online = $this->checkLogin();
        if (!$online) {
            $taskService = new TaskService();
            $user_task = $taskService->getAllTask();
        } else {
            $taskService = new TaskService($online);
            $user_task = $taskService->getAllUserCanTask();
        }

        $task = array();
        $data = array();
        foreach ($user_task as $value) {
            $data[$value['script_name']][] = $value;
        }
        // 临时处理为时间戳，后期前台可能会用到
        $task['id'] = time();
        $task['type'] = 'task';
        $task['data'] = $data;
        return new JsonResponse($task);
    }

    public function test($id)
    {
        $msg = $this->container->make('messageServer');
        $ms=$msg->getMessageByUid($this->checkLogin());
        return $this->render('Member/msglist1', array('data'=>$ms));
    }

    /**
     * 领取任务完成的奖励
     *  /task/end/(16)
     * @param $task_id int 任务id
     * @return JsonResponse
     */
    public function billTask($task_id)
    {
        $online = $this->checkLogin();
        if (!$online) {
            $msg = array('code' => 0, 'msg' => '未登录');
            return new JsonResponse($msg);
        } else {
            $taskService = new TaskService($online);
            $flag = $taskService->billTask($task_id);
        }

        if ($flag) {
            return new JsonResponse(array('code' => 1, 'msg' => '领取成功！'));
        } else {
            return new JsonResponse(array('code' => 0, 'msg' => '领取失败！请查看任务是否完成或已经领取过了！'));
        }
    }


}