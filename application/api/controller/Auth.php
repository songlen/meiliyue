<?php

namespace app\api\controller;
use think\Db;
use app\api\logic\SmsLogic;

class Auth extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}


    /**
     * 登录
     */
    public function login()
    {
        $mobile = trim(I('mobile'));
        $password = trim(I('password'));

        if (!$mobile || !$password) {
        	response_error('', '请填写账号或密码');
        }
        $user = Db::name('users')->where("account_mobile", $mobile)->find();
        if (!$user) {
            response_error('', '账号不存在！');
        } elseif (encrypt($password) != $user['password']) {
            response_error('', '密码错误！');
        } elseif ($user['is_lock'] == 1) {
            response_error('', '账号异常已被锁定！');
        }
        
        // 更新活跃时间、在线状态
        $updateData = array(
            'active_time' => time(),
            'is_line' => '1',
        );
        M('users')->where('user_id', $user['user_id'])->update($updateData);

        
        $userInfo = controller('Api/User')->getUserInfo($user['user_id']);
       	response_success($userInfo);
    }

    // 检测是否注册
    public function isRegister(){
    	$mobile = I('mobile');

    	$where['account_mobile'] = $mobile;
    	$count = M('users')->where($where)->count();
    	if($count){
    		response_error('', '该手机号已注册');
    	}
    	response_success('', '未注册');
    }
    // 检测手机验证码
    public function checkMobileCode(){
        $mobile = I('mobile');
        $code = I('code');
        // 验证码检测
        $SmsLogic = new SmsLogic();
        if($SmsLogic->checkCode($mobile, $code, '1', $error) == false) response_error('', $error);
        response_success('', '验证码正确');
    }

    /**
     *  注册
     */
    public function register() {
    	$mobile = I('mobile');
    	$code = I('code');
    	$password = trim(I('password'));
    	$nickname = I('nickname');
    	$sex = I('sex');
    	$birthday = I('birthday');
    	$country = I('country');
    	$province = I('province');
    	$city = I('city');
    	$qq = I('qq');

    	if(check_mobile($mobile) == false){
    		response_error('', '手机号格式错误');
    	}

    	$userInfo = Db::name('users')->where("account_mobile={$mobile}")->find();
    	if($userInfo){
    		response_error('', '该手机号已注册');
    	}

    	// 验证码检测
    	$SmsLogic = new SmsLogic();
        if($SmsLogic->checkCode($mobile, $code, '1', $error) == false) response_error('', $error);


    	if(empty($password)){
    		response_error('', '密码不能为空');
    	}

    	$uuid = generateUuid();
    	$map = array(
    		'account_mobile' => $mobile,
    		'nickname' => $nickname,
    		'account_mobile' => $mobile,
    		'account_mobile' => $mobile,
    		'password' => encrypt($password),
    		'uuid' => $uuid,
    		'nickname' => $mobile,
    		'reg_time' => time(),
    		'last_login' => time(),
    		'token' => md5(time().mt_rand(1,999999999)),
   			'sex' => $sex,
			'birthday' => $birthday,
			'country' => $country,
			'province' => $province,
			'city' => $city,
			'qq' => $qq,
            'active_time' => time(),
            'is_line' => '1',
    	);

    	$user_id = M('users')->insertGetId($map);
        if($user_id === false){
           response_error('', '注册失败');
        }
        
        $userInfo = controller('Api/User')->getUserInfo($user_id);
        return response_success($userInfo, '注册成功');
    }

    public function wx_login(){
        $openid = I('openid');
        $nickname = I('nickname');
        $headimgurl = I('headimgurl');
        $sex = I('sex/d');

        // 检测用户是否已注册
        $user = Db::name('users')->where("openid=$openid")->find();
        if($user){
            $user_id = $user['user_id'];
        } else {
            $data = array(
                'openid' => $openid,
                'nickname' => $nickname,
                'head_pic' => $headimgurl,
                'sex' => $sex,
                'token' => md5(time().mt_rand(1,999999999)),
            );

            $user_id = Db::name('users')->insertGetId($data);
        }

        $userInfo = $this->getUserInfo($user_id);

        response_success($userInfo);
    }

    /**
     * [sendMobleCode 发送手机验证码]
     * @param [scene 1 注册 2 找回密码]
     * @return [type] [description]
     */
    public function sendMobileCode(){
        $mobile = I('mobile');
        $scene = I('scene', 1);

        $SmsLogic = new SmsLogic();
        $code = $SmsLogic->send($mobile, $scene, $error);
        if($code != false){
            response_success(array('code'=>$code), '发送成功');
        } else {
            response_error('', $error);
        }
    }

    // 忘记密码
    public function resetPwd(){
        $mobile = I('mobile');
        $code = I('code');
        $password = I('password');
        $password_confirm = I('password_confirm');

        if(check_mobile($mobile) == false){
            response_error('', '手机号码有误');
        }
        // 检测验证码
        $SmsLogic = new SmsLogic();
        if($SmsLogic->checkCode($mobile, $code, '1', $error) == false) response_error('', $error);

        if($password != $password_confirm){
            response_error('', '两次密码输入不一致');
        }

        $user = Db::name('users')->where("mobile = $mobile")->find();
        if(empty($user)){
            response_error('', '手机号不存在');
        }

        $password = encrypt($password);
        Db::name('users')->where("mobile=$mobile")->update(array('password'=>$password));

        response_success('', '操作成功');
    }
}