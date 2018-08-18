<?php

namespace app\api\controller;
use think\Db;
use app\api\logic\SmsLogic;
use app\api\logic\FileLogic;
use app\api\logic\GeographyLogic;

class Vip extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}

	// 修改vip级别 0 普通会员 1 白金 2 黄金 3 vip
	public function change(){
		$user_id = I('user_id');
		$level = I('level');

		if(M('users')->where('user_id', $user_id)->setField('level', $level) !== false){
			response_success('', '修改成功');
		} else {
			response_error('', '修改失败');
		}
	}
}