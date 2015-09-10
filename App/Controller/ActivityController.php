<?php

namespace App\Controller;

use App\Models\ActivityPag;
class ActivityController extends BaseController
{
    /**
     * 活动详情页面
     *
     * @param $id int 活动的详细信息
     * @return \Core\Response
     */
    public function info($id)
    {
        $data = ActivityPag::where('img_text_id',$id)->where('dml_flag','!=',3)->first();
        $tmp = ActivityPag::where('pid',$id)->where('dml_flag','!=',3)->first();

        // 分割活动页面的图片
        $temp = explode(',',$tmp['temp_name']);
        $data['image'] = $temp[0]; // 第一个为原图
        $data['tmp'] = array_slice($temp,1); // 抛出第一个原图的 后面的才是切割后的图
        return $this->render('Activity/info',array('activity'=>$data));
    }
}