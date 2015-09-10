<?php


namespace App\Controller;

class RankController extends BaseController
{
    public function index()
    {
        return $this->render('Rank/index',array());
    }
}