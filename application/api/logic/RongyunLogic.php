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

	public function getToken($user_id, $nickname, $head_pic = ''){
		if($head_pic != ''){
			if(!strpos($head_pic, 'http')){
				$head_pic = config('host_url').$head_pic;
			}
		} else {
			$head_pic = config('host_url').'public/images/tx.png';
		}
		// 获取 Token 方法
		$result = $this->RongCloud->user()->getToken($user_id, $nickname, $head_pic);
		$result = json_decode($result, true);
		p($result);
		if($result['code'] == 200){
			$rongyun_token = $result['token'];
			M('users')->where('user_id', $user_id)->setField('rongyun_token', $rongyun_token);
			return $rongyun_token;
		} else {
			return false;
		}
	}

	// 发送系统消息
	public function PublishSystemMessage($formUserId, $toUserId, $content){
		return $this->RongCloud->Message()->PublishSystem($formUserId, $toUserId, 'RC:TxtMsg', $content);
	}

	// 发送单聊消息
	public function PublishPrivateMessage($toUserId, $content){
		// 
		$toUserId = array($toUserId);
		$content = json_encode(array('content' => $content));
		return $this->RongCloud->Message()->publishPrivate('5', $toUserId, 'RC:TxtMsg', $content);
	}


}