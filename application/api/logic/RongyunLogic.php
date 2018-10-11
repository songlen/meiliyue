<?php
/*
 * 融云接口类
 */

namespace app\api\logic;

use think\Controller;
use think\Db;

include PLUGIN_PATH.'rongyun/rongcloud.php';

class RongyunLogic extends Controller {


	private $appKey;
	private $appSecret;
	private $jsonPath;
	private $RongCloud;

	public function __construct(){

		$this->appKey = 'tdrvipkstxgq5';
		$this->appSecret = 'F1QPkVpi57B08';
		$this->jsonPath = "jsonsource/";
		$this->RongCloud = new \RongCloud($this->appKey,$this->appSecret);
	}

	public function getToken($user_id, $nickname, $head_pic){
		// 获取 Token 方法
		$result = $this->RongCloud->user()->getToken($user_id, $nickname, $head_pic);
		return $result;
	}

	// 发送系统消息
	public function PublishSystemMessage($formUserId, $toUserId, $content){
		return $this->RongCloud->Message()->PublishSystem($formUserId, $toUserId, 'RC:TxtMsg', $content);
	}
}