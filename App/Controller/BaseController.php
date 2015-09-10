<?php
namespace App\Controller;

use Core\Controller;

class BaseController extends  Controller
{

    const CLIENT_ENCRY_FIELD = 'v_remember_encrypt';
    const SEVER_SESS_ID = 'webonline';//在线用户id
    const  TOKEN_CONST = 'auth_key';
    const WEB_UID = 'webuid';
    const  WEB_SECRET_KEY = 'c5ff645187eb7245d43178f20607920e456';
    protected  $_online;

    /**
     * 检查是否登录了，这里是根据原有的sf2中的session来判断的
     * 以后如果废掉sf2之后，有必要的话，是可以修改掉的
     */
    public function checkLogin()
    {
        if($this->_online){
            return $this->_online;
        }
        if(isset($_SESSION['_sf2_attributes'][self::SEVER_SESS_ID]) && $_SESSION['_sf2_attributes'][self::SEVER_SESS_ID] != null){
            $this->_online = $_SESSION['_sf2_attributes'][self::SEVER_SESS_ID];
        }

        return $this->_online;
    }

}