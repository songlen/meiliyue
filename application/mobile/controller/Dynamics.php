<?php

namespace app\mobile\controller;

use think\Db;
use think\Config;

 
class Dynamics extends Base {

    /**
     * [index 动态列表]
     * @param [range] $[range] [范围 1 同城 2 全网]
     * @return [type] [description]
     */
    public function index(){
        
        return $this->fetch();
    }

    /**
     * [add description]
     */
    public function add(){
        
        return $this->fetch();
    }

    public function detail(){
        $id = I('id');

        $where['d.status'] = '2';
        $where['d.id'] = $id;
        $info = Db::name('dynamics')->alias('d')
            ->join('users u', 'd.user_id=u.user_id', 'left')
            ->where($where)
            ->field('u.user_id, head_pic, nickname, u.sex, u.age, d.id dynamic_id, d.type, d.content, d.location, d.add_time, d.flower_num')
            ->find();

        if($info) {
            $info['content'] = html_entity_decode($info['content']);
        }

        // 图片类型，取出图片
        if($info['type'] == '2'){
            $dynamics_image = M('dynamics_image')->where('dynamic_id', $info['dynamic_id'])->field('image')->select();
            $image = array();
            if(is_array($lists) && !empty($lists)){
                foreach ($dynamics_image as $v) {
                    $image[] = $v['image'];
                }
            }
            $info['image'] = $image;
        }
        // 视频
        if($info['type'] == '3'){
            $dynamics_image = M('dynamics_image')->where('dynamic_id', $info['dynamic_id'])->field('image')->find();
            
            $info['video_thumb'] = $dynamics_image['image'];
        }

        /*************** 获取查看此条动态的人 ****************/
        $viewers = Db::name('dynamics_viewer')->alias('dv')
            ->join('users u', 'dv.viewer_id=u.user_id', 'left')
            ->where('dynamic_id', $id)
            ->field('head_pic')
            ->order('dv.id desc')
            ->select();

        /********* 记录查看此条动态的人 **************/
        $viewer_id = 1;
        if($viewer_id != $info['user_id']){
            $is_exist = M('dynamics_viewer')->where(array('dynamic_id'=>$id, 'viewer_id'=>$viewer_id))->find();
            if(!$is_exist){
                M('dynamics_viewer')->insert(array('dynamic_id'=>$id, 'viewer_id'=>$viewer_id));
            }
        }
p($info);

        $this->assign('info', $info);
        $this->assign('viewers', $viewers);
        $this->assign('comments', $comments);

        return $this->fetch();
    }
}