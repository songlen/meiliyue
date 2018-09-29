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
}