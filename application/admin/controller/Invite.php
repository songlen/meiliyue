<?php

namespace app\admin\controller;
use think\Db;
use think\Page;

class Invite extends Base{
	public function index(){
		$p = I('p', 1);

		$where = " 1 = 1 ";
        $keywords = trim(I('keywords'));
        $keywords && $where.=" and title like '%$keywords%' ";
       
       	$pagesize = 20;
        $lists = M('invite')->where($where)->order('id desc')->page("$p, $pagesize")->select();
        $count = M('invite')->where($where)->count();// 查询满足要求的总记录数
        $Page = new Page($count, $pagesize);// 实例化分页类 传入总记录数和每页显示的记录数


        $this->assign('lists', $lists);
        $this->assign('Page', $Page);
		return $this->fetch();
	}
}