<?php

namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
use app\api\logic\MessageLogic;

class Withdraw extends Base {

    public function index(){
        return $this->fetch();
    }

    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
        I('email') ? $condition['email'] = I('email') : false;

        $model = M('Withdraw');
        $count = $model->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $lists = $model->alias('w')->where($condition)
        	->join('users u', 'w.user_id=u.user_id', 'left')
        	->order('id desc')
        	->limit($Page->firstRow.','.$Page->listRows)
        	->field('u.user_id, u.nickname, u.uuid, w.id, w.money, w.account, w.name, w.createtime, w.status, w.mark')
        	->select();

                           
        $show = $Page->show();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function detail(){
    	$id = I('id');
        if(IS_POST){
            $data = array(
                'status' => I('status'),
                'mark' => I('mark'),
            );

            if(false !== Db::name('withdraw')->where('id', $id)->update($data)){
                exit($this->success('修改成功', U('withdraw/index')));
            }
            exit($this->error('未作内容修改或修改失败'));
        }
    	$info = M('Withdraw')->alias('w')
            ->join('users u', 'w.user_id=u.user_id', 'left')
            ->where('w.id', $id)
            ->find();

    	$this->assign('info', $info);
    	return $this->fetch();
    }

    public function changeStatus(){
    	$id = I('id');
    	$status = I('status');
    	$user_id = I('user_id');

    	$result = M('Withdraw')->where('id', $id)->setField('status', $status);

        // 发送站内消息
        // if($result && in_array($status, array(2, 3))){
        //     $messsage = $status == 2 ? '恭喜您，身份认证已通过' : '很抱歉，身份认证未通过';
        //     $MessageLogic = new MessageLogic();
        //     $MessageLogic->add($user_id, $messsage);
        // }
    }
 }