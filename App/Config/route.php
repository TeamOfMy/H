<?php
if(!defined('BASEDIR')){
    exit('File not found');
}

// 所有路由都在这里配置

// 任务api
$app->get('/task',['as'=>'task_index','uses'=>'App\Controller\TaskController@index']);
// 任务完成领取奖励api
$app->get('/task/end/{id:\d+}',['uses'=>'App\Controller\TaskController@billTask']);

// 用户中心消息  type 是可有可无的 必须放到最后
$app->get('/task/member/msglist[/{type:\d+}]',['as'=>'member_msglist','uses'=>'App\Controller\MemberController@message']);

// 排行榜页面
$app->get('/task/rank',['as'=>'task_info','uses'=>'App\Controller\RankController@index']);

// 活动详情页面
$app->get('/nac/{id:\d+}',['as'=>'ac_info','uses'=>'App\Controller\ActivityController@info']);

// test 测试用
$app->get('/task/bb',function(){
    return new \Symfony\Component\HttpFoundation\Response('4455566');
});