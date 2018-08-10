<?php


namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class UserAuthVideo extends Base {

    public function index(){
    	
        return $this->fetch();
    }

    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
        I('email') ? $condition['email'] = I('email') : false;
               
        $model = M('UsersAuthVideo');
        $count = $model->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $lists = $model->alias('uav')->where($condition)
        	->join('users u', 'uav.user_id=u.user_id', 'left')
        	->order('id desc')
        	->limit($Page->firstRow.','.$Page->listRows)
        	->field('u.user_id, u.nickname, u.uuid, uav.id, uav.auth_video_url, uav.status, uav.add_time')
        	->select();
                           
        $show = $Page->show();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function video(){
    	$id = I('id');

    	$video = M('UsersAuthVideo')->where('id', $id)->field('auth_video_url')->find();

    	$this->assign('auth_video_url', $video['auth_video_url']);
    	return $this->fetch();
    }

    public function changeStatus(){
    	$id = I('id');
    	$status = I('status');
    	$user_id = I('user_id');

    	M('UsersAuthVideo')->where('id', $id)->setField('status', $status);
    	M('users')->where('user_id', $user_id)->setField('auth_video_status', $status);

    }
 }