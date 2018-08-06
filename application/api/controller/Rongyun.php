<?php

namespace app\api\controller;

use think\Db;

include PLUGINS_PATH.'rongyun/rongcloud.php';

class Rongyun extends Base {


	private $appKey;
	private $appSecret;
	private $jsonPath;
	private $RongCloud;

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();

		$this->appKey = 'tdrvipkstxgq5';
		$this->appSecret = 'F1QPkVpi57B08';
		$this->jsonPath = "jsonsource/";
		$this->RongCloud = new RongCloud($appKey,$appSecret);


	}

	public function getToken(){
		$user_id = I('user_id');
		$user = M('users')->where('user_id', $user_id)->field('nickname, head_pic, rongyun_token')->find();
		if(empty($user)) response_error('', '该用户不存在');

		
		// 获取 Token 方法
		$result = $this->RongCloud->user()->getToken('userId1', 'username', 'http://www.rongcloud.cn/images/logo.png');
	}
}