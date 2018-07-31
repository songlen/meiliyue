<?php

namespace app\mobile\controller;

use think\Db;
use think\Config;

 
class Invite extends Base {
	// 登录成功后加载的隐藏页面 用于前端保存用户信息
	public function saveUserInfo(){
		$user_id = I('user_id');

		$userinfo =  M('users')->where('user_id', $user_id)->find();

		$this->assign('userinfo', $userinfo);
		$this->fetch();
	}

	public function clear(){

	}
}

