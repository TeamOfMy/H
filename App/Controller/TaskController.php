<?php
namespace App\Controller;
use Core\Controller;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{

    public function index()
    {
        return new Response('test!');
    }

    public function test()
    {
       return  $this->render('Task/info.html',array('id'=>8));
    }

}