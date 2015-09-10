<?php
namespace App\Controller;

class MemberController extends BaseController
{
    private $_menus = array(
        array(
            'action'=>'index',
            'name'=>'基本信息',
            'ico'=>null,
        ),
        array(
            'action'=>'invite',
            'name'=>'推广链接',
            'ico'=>7,
        ),
        array(
            'action'=>'attention',
            'name'=>'我的关注',
            'ico'=>6,
        ),
        array(
            'action'=>'scene',
            'name'=>'我的道具',
            'ico'=>3,
        ),
        array(
            'action'=>'charge',
            'name'=>'充值记录',
            'ico'=>5,
        ),
        array(
            'action'=>'consumerd',
            'name'=>'消费记录',
            'ico'=>8,
        ),
        array(
            'action'=>'password',
            'name'=>'密码管理',
            'ico'=>2,
        ),//主播才有
        array(
            'action'=>'roomset',
            'name'=>'房间设置',
            'ico'=>2,
        ),
        array(
            'action'=>'myReservation',
            'name'=>'我的预约',
            'ico'=>2,
        ),//主播才有
        array(
            'action'=>'withdraw',
            'name'=>'提现',
            'ico'=>9,
        ),
        array(
            'action'=>'anchor',
            'name'=>'主播中心',
            'ico'=>4,
        ),//主播才有
        array(
            'action'=>'gamelist',
            'name'=>'房间游戏',
            'ico'=>4,
        ),//主播才有
        array(
            'action'=>'gift',
            'name'=>'礼物统计',
            'ico'=>7,
        ),//主播才有
        array(
            'action'=>'live',
            'name'=>'直播记录',
            'ico'=>8,
        ),//主播才有
        array(
            'action'=>'msglist',
            'name'=>'消息',
            'ico'=>'0',
        )
    );

    protected function __init__()
    {
        if(!$this->checkLogin()){
            exit('请登录！');
        }
    }
    protected $_userinfo;
    public function render($tpl,$params=[])
    {
        if( $this->_userinfo['roled'] == 3){
            $params['menus_list'] = $this->_menus;
        }else{
            $params['menus_list'] = array();
            foreach( $this->_menus as $key=>$item ){
                if( !in_array($item['action'], array('anchor', 'live', 'withdraw', 'roomset')) ){
                    $params['menus_list'][] = $item;
                }
            }
        }

        return parent::render($tpl,$params);
    }


    /**
     * 用户中心消息列表页面
     *
     * @param int $type 消息类型 默认2为私信
     *  1 系统消息
     *  2 私信
     * @return \Core\Response
     */
    public function message($type=2)
    {
        // 调用消息服务
        $msg = $this->container->make('messageServer');

        // 根据用户登录的uid或者用户消息的分页数据
        $ms=$msg->getMessageByUidAndType($this->checkLogin(),$type);

        // 不同的消息类型做不同的模板
        $tpl = 'Member/msglist'.$type;
        return $this->render($tpl, array('data'=>$ms,'msglist1'=>'系统消息','msglist2'=>'私信'));
    }

}