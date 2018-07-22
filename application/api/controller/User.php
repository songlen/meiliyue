<?php

namespace app\api\controller;
use think\Db;
use app\api\logic\SmsLogic;
use app\api\logic\FileLogic;

class User extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
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

    /**
     * [uploadFile 上传头像/认证视频]
     * @param [type] $[type] [文件类型 head_pic 头像 auth_video 视频认证]
     * @param  $[action] [ 默认 add 添加 edit 修改]
     * @return [type] [description]
     */
    public function uploadFile(){
        $user_id = I('user_id/d');
        $file = $this->request->file('file');
        $type = I('type'); 
        $action = I('action', 'add');

        // $image_upload_limit_size = config('image_upload_limit_size');
        // 上传路径
       if($type == 'head_pic'){
            // $validate = ['ext'=>'jpg,png,gif,jpeg'];
            $uploadPath = UPLOAD_PATH.'head_pic/';
       }
       if($type == 'auth_video'){
            $uploadPath = UPLOAD_PATH.'auth_video/';
       }

        if (!file_exists($uploadPath)){
            mkdir($uploadPath, 0777, true);
        }
        // 执行上传
        // $file->validate($validate);
        $info = $file->move($uploadPath, true);
        if($info){
            $parentDir = date('Ymd'); // 系统默认在上传目录下创建了日期目录
            $fullPath = '/'.$uploadPath.$parentDir.'/'.$info->getFilename();
            // 修改用户表头像记录
            if($type == 'head_pic'){
                Db::name('users')->update(array('user_id'=>$user_id, 'head_pic'=>$fullPath));
                /************ 更新动态 *********/
                $dynamics_data = array(
                    'user_id' => $user_id,
                    'type' => 2,
                    'description' => '更新了形象照',
                    'origin' => 2,
                    'add_time' => time(),
                );
                D('dynamics')->add($data);

                response_success(array('head_pic'=>$fullPath));
            }
            // 记录认证视频
            if($type == 'auth_video' && $action == 'add'){
                Db::name('users_auth_video')->insert(array('user_id'=>$user_id, 'auth_video_url'=> $fullPath));
            }
            // 如果是修改认证视频
            if($type == 'auth_video' && $action == 'edit'){
                Db::name('users_auth_video')->update(array('user_id'=>$user_id, 'auth_video_url'=> $fullPath));
                // 如果需要重新认证还需要修改用户表中的认证字段
            }
            /************ 上传形象视频，更新动态 *********/
            if($type == 'auth_video'){
                $dynamics_data = array(
                    'user_id' => $user_id,
                    'type' => 3,
                    'description' => '更新了形象照',
                    'origin' => 3,
                    'add_time' => time(),
                );
                D('dynamics')->add($data);
            }

            response_success();
        }else{
            response_error('', $file->getError());
        }
    }

    // 常见问题
    public function questions(){
        $user_id = I('user_id/d');

        $where = array(
            'cat_id' => '1',
            'is_open' => '1',
        );
        $questions = Db::name('article')->where($where)
            ->order('article_id desc')
            ->field('article_id, title, description, content')
            ->select();

        if(!empty($questions)){
            foreach ($questions as &$item) {
                $item['content'] = html_entity_decode($item['content']);
                $item['link'] = '/web/#/article?id='.$item['article_id'];
            }
        }
        response_success($questions);
    }

    /**
     * [getUserInfo 获取用户基本资料]
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    public function getUserInfo($user_id){
    	$userInfo = M('users')->where("user_id", $user_id)
    		->field('user_id, sex, account_mobile, nickname, head_pic')
    		->find();
       
       return $userInfo;
    }

    public function message(){
        $user_id = I('user_id/d');
        $page = I('page/d', 1);

        $limit_start = ($page-1)*20;

        $message = Db::name('message')->alias('m')
            ->join('user_message um', 'um.message_id=m.message_id', 'left')
            ->where('user_id', $user_id)
            ->whereOr('m.type', 1)
            ->field('m.message_id, message, m.category, data, send_time, status')
            ->order('message_id desc')
            ->limit($limit_start, 20)
            ->select();

        if(!empty($message)){
            $now_date = strtotime(date('Y-m-d')); // 今日凌晨
            $mid_date = strtotime(date('Y-m-d 12:00:00')) ;// 今日中午

            foreach ($message as &$item) {
                if($item['send_time'] < $now_date) $item['send_time'] = date('Y-m-d', $item['send_time']);
                if($item['send_time'] > $now_date && $item['send_time'] < $mid_date) $item['send_time'] = '上午'.date('H:i', $item['send_time']);
                if($item['send_time'] > $mid_date) $item['send_time'] = '下午'.date('H:i', $item['send_time']);

                if($item['data']) $item['data'] = unserialize($item['data']);
            }
        }

        response_success($message);
    }

    public function identityAuth(){
        $user_id = I('user_id');

        $uploadPath =  UPLOAD_PATH.'identityAuth/';
        $FileLogic = new FileLogic();
        $uploadResult = $FileLogic->uploadMultiFile('file', $uploadPath);
        if($uploadResult['status'] == '1'){
            $image = $uploadResult['image'];
        }

        $data = array(
            'user_id' => $user_id,
            'image' => serialize($image),
            'add_time' => time(),
        );

        if(M('identity_auth')->insert($data)){
            response_success('', '操作成功');
        } else {
            response_error('', '操作失败');
        }
    }

    // 关注
    public function attention(){
        $user_id = I('user_id');
        $friend_id = I('friend_id');

        // 防止重复关注
        if(M('friend')->where(array('user_id'=>$user_id, 'friend_id'=>$friend_id))->count()){
            response_error('', '已关注');
        }


        $data = array(
            'user_id' => $user_id,
            'friend_id' => $friend_id,
            'add_time' => time(),
        );

        $insert_id = M('friend')->insertGetId($data);
        if($insert_id){
             // 查看是否被关注
            $friend = M('friend')->where(array('user_id'=>$friend_id, 'friend_id'=>$user_id))->find();
            if($friend){
                M('friend')->where('id', $insert_id)->whereOr('id', $friend['id'])->setField('twoway', 1);
            }

            response_success('', '关注成功');
        } else {
            response_error('', '关注失败');
        }
    }

    public function cancelAttention(){
        
    }
}