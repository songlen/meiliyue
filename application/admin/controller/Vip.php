<?php


namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
use app\api\logic\MessageLogic;

class Vip extends Base {

    public function index(){
    	
        return $this->fetch();
    }

    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        // I('mobile') ? $condition['mobile'] = I('mobile') : false;
        // I('email') ? $condition['email'] = I('email') : false;
               
        $model = M('vip_order');
        $count = $model->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $lists = $model->alias('vo')->where($condition)
        	->join('users u', 'vo.user_id=u.user_id', 'left')
        	->order('id desc')
        	->limit($Page->firstRow.','.$Page->listRows)
        	->field('u.user_id, u.nickname, u.uuid, vo.id, vo.level, vo.paystatus, vo.paytime')
        	->select();

                           
        $show = $Page->show();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
 }