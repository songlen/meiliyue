<?php


namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class IdentityAuth extends Base {

    public function index(){
    	
        return $this->fetch();
    }

    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
        I('email') ? $condition['email'] = I('email') : false;
               
        $model = M('IdentityAuth');
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
        	->field('u.user_id, u.nickname, u.uuid, uav.id, uav.image, uav.status, uav.add_time')
        	->select();

        // if(is_array($lists) && !empty($lists)){
        // 	foreach ($lists as &$item) {
        // 		$item['image'] = unserialize($item['image']);
        // 	}
        // }
                           
        $show = $Page->show();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function image(){
    	$id = I('id');

    	$info = M('IdentityAuth')->where('id', $id)->field('image')->find();

    	$this->assign('image', unserialize($info['image']));
    	return $this->fetch();
    }

    public function changeStatus(){
    	$id = I('id');
    	$status = I('status');
    	$user_id = I('user_id');

    	M('IdentityAuth')->where('id', $id)->setField('status', $status);
    	M('users')->where('user_id', $user_id)->setField('auth_identity_status', $status);

    }
 }