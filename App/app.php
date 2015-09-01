<?php

$loader = include BASEDIR.'/../vendor/autoload.php';
spl_autoload_register(array($loader,'loadClass'));
$app = new \Core\Application();


$app->get('/task',['uses'=>'App\Controller\TaskController@index']);
$app->get('/task/{id:\d+}',['uses'=>'App\Controller\TaskController@test']);
$app->get('/task/bb',function(){
    return new \Symfony\Component\HttpFoundation\Response('4455566');
});
return $app;