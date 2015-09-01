<?php
return array(
    array('get|post','/task','\App\Controller\TaskController:index','task_indx'),
    array('get|post','/task/end/[i:id]','\App\Controller\TaskController:billTask','task_bill'),
    array('get','/nac/[i:id]','\App\Controller\ActivityController:info','ac_info'),
    array('get','/task/t','\App\Controller\TaskController:test','ac_info'),
);