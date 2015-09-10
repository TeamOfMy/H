<?php
if(!defined('BASEDIR')){
    exit('File not found');
}
// 加载自动加载类中 composer方式 并注册到系统中
$loader = include BASEDIR.'/vendor/autoload.php';
spl_autoload_register(array($loader,'loadClass'));

// 初始化框架入口 容器
$app = new \Core\Application();

// 注册服务到框架中，全局都可以调用 调用时候 $container->make(service)
$app->alias('App\Service\Task\TaskService','taskServer');
$app->alias('App\Service\Message\MessageService','messageServer');

// 加载项目所有路由配置
include 'Config/route.php';

return $app;