<?php

namespace app\admin\controller;

use think\Page;
use think\Db;

class Goldcoin extends Base {

    public function index(){

        $p = $this->request->param('p');
        $list = M('Goldcoin')
            ->where('is_delete', 0)
            ->order('sort desc, id asc')
            ->page($p.',10')
            ->select();

        
        $count = M('Goldcoin')->where('is_delete', 0)->count();
        $Page = new Page($count,10);
        $show = $Page->show();

        $this->assign('list',$list);
        $this->assign('page',$show);
        return $this->fetch();
    }

    public function add(){
        if(IS_POST) {
            $data = I('post.');
            if( ! false == M('Goldcoin')->save($data)){
                $this->ajaxReturn(array('status'=>1, 'msg'=>'操作成功'));
            } else {
                $this->ajaxReturn(array('status'=>0, 'msg'=>'操作失败'));
            }
        }
        return $this->fetch();
    }

    public function edit(){
        $id = I('id');

        if(IS_POST) {
            $data = I('post.');
            if( ! false == M('Goldcoin')->where('id', $id)->save($data)){
                $this->ajaxReturn(array('status'=>1, 'msg'=>'操作成功'));
            } else {
                $this->ajaxReturn(array('status'=>0, 'msg'=>'操作失败'));
            }
        }

        $info = M('Goldcoin')->where('id', $id)->find();

        $this->assign('info', $info);
        return $this->fetch();
    }

    public function del(){
        $id = I('id');

        if(! FALSE ==  M('Goldcoin')->where('id', $id)->update(array('is_delete'=>1))){
            $this->ajaxReturn(array('status'=>1, 'msg'=>'操作成功'));
        } else {
            $this->ajaxReturn(array('status'=>0, 'msg'=>'操作失败'));
        }
    }

    // 购买记录
    public function order(){

        $p = I('p', 1);

        $where = array(
            'paystatus'=>1,
        );
        $list = Db::name('goldcoin_order')->alias('go')
            ->join('users u', 'go.user_id=u.user_id', 'left')
            ->where($where)
            ->order('id desc')
            ->page($p.',1')
            ->field('u.nickname, u.uuid, go.num, go.give_num, go.price, go.paytime')
            ->select();
        
        $count = Db::name('goldcoin_order')->where($where)->count();
        $Page = new Page($count,1);
        $show = $Page->show();

        $this->assign('list', $list);
        $this->assign('show', $show);
        return $this->fetch();
    }
}