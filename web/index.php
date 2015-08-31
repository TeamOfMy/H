<?php

require '../vendor/autoload.php';



define('BASEDIR',__DIR__);
$loader = include BASEDIR.'/../vendor/autoload.php';
spl_autoload_register(array($loader,'loadClass'));
$core = new \Core\Application();

$core->get('/task',['uses'=>'App\Controller\TaskController@index']);

// $core->run();
$core->goRun();
//$response->send();