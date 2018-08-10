<?php

namespace app\api\controller;
use think\Db;
use app\api\logic\SmsLogic;
use app\api\logic\FileLogic;
use app\api\logic\GeographyLogic;

class User extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
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

        if (!is_dir($uploadPath)){
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
                    'image' => array($fullPath),
                    'origin' => 2,
                    'add_time' => time(),
                );
                D('dynamics')->add($dynamics_data);

                response_success(array('head_pic'=>$fullPath));
            }
            // 记录认证视频
            if($type == 'auth_video'){
                $count = Db::name('users_auth_video')->where('user_id', $user_id)->count();
                if($count){
                    Db::name('users_auth_video')->where('user_id', $user_id)->update(array('auth_video_url'=> $fullPath, 'add_time' => time()));
                } else {
                    Db::name('users_auth_video')->insert(array('user_id'=>$user_id, 'auth_video_url'=> $fullPath, 'add_time' => time()));

                }
                // 更新用户表视频认证状态
                Db::name('users')->where('user_id', $user_id)->setField('auth_video_status', 1);
                /************ 上传形象视频，更新动态 *********/
                /*if($type == 'auth_video'){
                    $dynamics_data = array(
                        'user_id' => $user_id,
                        'type' => 3,
                        'description' => '更新了形象视频',
                        'video' => $fullPath,
                        'origin' => 3,
                        'add_time' => time(),
                    );
                    D('dynamics')->add($dynamics_data);
                }*/
            }

            response_success();
        }else{
            response_error('', $file->getError());
        }
    }

    // 常见问题
    public function questions(){

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
                $item['link'] = '/mobile/Article/questionDetail?article_id='.$item['article_id'];
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
    	$userInfo = M('users')->where("user_id", $user_id)->find();
        unset($userInfo['password']);
       
       response_success($userInfo);
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

    public function commentList(){
        $user_id = I('user_id');
        $page = I('page', 1);

        $start_limit = ($page-1)*20;
        $lists =  M('dynamics_comment')->alias('dc')
            ->join('dynamics d', 'd.id=dc.dynamic_id', 'left')
            ->join('users u', 'u.user_id=dc.commentator_id', 'left')
            ->where('dc.reply_user_id', $user_id)
            ->field('d.type, d.description, d.content,u.user_id, u.head_pic, u.nickname, u.auth_video_status, u.sex, u.age, dc.dynamic_id, dc.content, dc.parent_id, dc.add_time')
            ->limit($start_limit, 20)
            ->select();

        if(is_array($lists) && !empty($lists)){
            foreach ($lists as &$item) {
                if(in_array($item['type'], array('2', '3'))){
                    $image = M('dynamics_image')->where('dynamic_id', $item['dynamic_id'])->find();
                    $item['image'] = $image['image'] ?  $image['image'] : '';
                    $item['video'] = $image['video'] ?  $image['video'] : '';
                }
            }
        }

        response_success($lists);
    }

    // 身份认证
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
        $user_id = I('user_id');
        $friend_id = I('friend_id');


        M('friend')->where(array('user_id'=>$user_id, 'friend_id'=>$friend_id))->delete();
        M('friend')->where(array('user_id'=>$friend_id, 'friend_id'=>$user_id))->setField('twoway', 0);
        response_success('', '操作成功');
    }

    public function homePage(){
        $user_id = I('user_id');
        $toUserId = I('toUserId');

        if($user_id == $toUserId){
            $this->redirect('mobile/user/myHomePage', array('user_id' => $user_id));
        }
        /************ 获得自己的信息 **************/
        $user = M('users')->where('user_id', $user_id)->find();

        /*********** 获得对方信息 ************/
        $toUserInfo = M('users')->where('user_id', $toUserId)->find();
        unset($toUserInfo['password']);

        $data['baseinfo'] = $toUserInfo;
        $data['baseinfo']['province'] = $toUserInfo['province'] ? getRegionNameByCode($toUserInfo['province']) : '';
        $data['baseinfo']['city'] = $toUserInfo['city'] ? getRegionNameByCode($toUserInfo['city']) : '';
        //计算距离
        $GeographyLogic = new GeographyLogic();
        $data['baseinfo']['dinstince'] = $GeographyLogic->getDistance($user['longitude'], $user['latitude'], $toUserInfo['longitude'], $toUserInfo['latitude']);
        
        /************* 是否关注 *************/
        $data['baseinfo']['attention'] = 0; // 未关注
        $friend = M('friend')->where(array('user_id'=>$user['user_id'], 'friend_id'=>$toUserId))->find();
        if($friend){
            $data['baseinfo']['attention'] = 1; // 已关注
            if($friend['twoway']) $data['baseinfo']['attention'] = 3; // 好友
        } else {
            $friend = M('friend')->where(array('user_id'=>$toUserId, 'friend_id'=>$user['user_id']))->find();
            if($friend) $data['baseinfo']['attention'] = 2; // 被关注
        }
        
        /************** 他的邀约 *********/
        $data['invite'] = M('invite')->where('user_id', $toUserId)
            ->field('id, title')
            ->order('id desc')
            ->limit(3)
            ->select();
        /************** 他的动态 *********/
        $data['dynamics'] = M('invite')->where('user_id', $toUserId)
            ->field('id, title')
            ->order('id desc')
            ->find();

        /************** 他的照片 *********/
        $data['photos'] = M('user_photo')->where('user_id', $toUserId)
            ->field('id, thumb, url, type, file_type')
            ->order('id desc')
            ->select();


        response_success($data);
    }

    public function myHomePage(){
        $user_id = I('user_id', 1);
        /************ 获得自己的信息 **************/
        $user = M('users')->where('user_id', $user_id)->find();
        unset($user['password']);

        $data['baseinfo'] = $user;

        /************** 我的的邀约 *********/
        $data['invite'] = M('invite')->where('user_id', $user_id)
            ->field('id, title')
            ->order('id desc')
            ->limit(3)
            ->select();
        /************** 我的的动态 *********/
        $data['dynamics'] = M('invite')->where('user_id', $user_id)
            ->field('id, title')
            ->order('id desc')
            ->find();

        /************** 我的照片 *********/
        $data['photos'] = M('user_photo')
            ->where('user_id', $user_id)
            ->where('type', 1)
            ->field('id, thumb, url, type, file_type')
            ->order('id desc')
            ->select();

        /************** 我的精华照片 *********/
        $data['quintessence_photos'] = M('user_photo')
            ->where('user_id', $user_id)
            ->where('type', 2)
            ->field('id, thumb, url, type, file_type')
            ->order('id desc')
            ->select();

        response_success($data);
    }

    /**
     * [uploadPhoto 上传照片、精华照片]
     * type 1 普通照片 2  精华照片
     * file_type 1 图片 2 视频
     * @return [type] [description]
     */
    public function uploadPhoto(){
        $user_id = I('user_id/d');
        $type = I('type'); 
        $file_type = I('file_type', 1); 

        $uploadPath = UPLOAD_PATH.'photo/';
        $FileLogic = new FileLogic();
        $uploadResult = $FileLogic->uploadMultiFile('file', $uploadPath);

        if($uploadResult['status'] == '1'){
            $images = $uploadResult['image'];
            foreach ($images as $imageUrl) {
                $photoData = array(
                    'user_id' => $user_id,
                    'thumb' => $imageUrl,
                    'url' => $imageUrl,
                    'type' => $type,
                    'add_time' => time(),
                    'file_type' => $file_type,
                );
                M('user_photo')->insert($photoData);
            }
            
            // 发表动态
            $description = $type == '1' ? '上传了照片到相册' : '上传了精华照片到相册';
            $dynamics_data = array(
                'user_id' => $user_id,
                'type' => 2,
                'description' => $description,
                'image' => $images,
                'origin' => 4,
                'add_time' => time(),
            );
            D('dynamics')->add($dynamics_data);

            response_success(array('files'=>$images));

        }else{
            response_error('', $file->getError());
        }
    }

    /**
     * [changeLocation 更新位置]
     * @return [type] [description]
     */
    public function changeLocation(){
        $user_id = I('user_id', 1);
        $data['province'] = I('province');
        $data['city'] = I('city');
        $data['longitude'] = I('longitude');
        $data['latitude'] = I('latitude');

        if(M('users')->where('user_id', $user_id)->update($data) !== false){
            response_success('', '操作成功');
        } else {
            response_error('', '操作失败');
        }
    }

    /**
     * [changeInfo 编辑个人资料]
     * @return [type] [description]
     */
    public function changeInfo(){
        $user_id = I('user_id');
        $field = I('field');
        $fieldValue = I('fieldValue');

        if(M('users')->where('user_id', $user_id)->setField($field, $fieldValue) !== false){
            response_success('', '操作成功');
        } else {
            response_error('', '操作失败');
        }
    }

    /**
     * [visitors 来访者]
     * @param type 1 来访者 2 我看过的人
     * @return [type] [description]
     */
    public function visitor(){
        $user_id = 1;
        $page = I('page', 1);
        $type = I('type', 1);

        if($type == '1'){
            $join_on = 'uv.user_id = u.user_id';
            $where['uv.to_user_id'] = $user_id;
            $field = 'uv.user_id, head_pic, nickname, age, sex, uv.add_time, signature';
        } else {
            $join_on = 'uv.to_user_id = u.user_id';
            $where['uv.user_id'] = $user_id;
            $field = 'to_user_id user_id, head_pic, nickname, age, sex, uv.add_time, signature';
        }

        $limit_start = ($page-1)*10;
        $lists = M('user_visitor')->alias('uv')
            ->join('users u', $join_on, 'left')
            ->where($where)
            ->field($field)
            ->order('uv.id desc')
            ->limit($limit_start, 10)
            ->select();


        response_success($lists);
    }

    /**
     * [friend 我的好友]
     * @return [type] [description]
     */
    public function friend(){
        $user_id = I('user_id', 1);

        $lists = M('friend')->alias('f')
            ->join('users u', 'f.friend_id = u.user_id', 'left')
            ->where('f.user_id', $user_id)
            ->where('twoway', '1')
            ->order('f.id desc')
            ->field('friend_id user_id, head_pic, nickname, auth_video_status')
            ->select();

        response_success($lists);
    }

    /**
     * [friend 关注和粉丝]
     * @param type 1 关注 2 粉丝
     * @return [type] [description]
     */
    public function attentionFans(){
        $user_id = I('user_id', 1);
        $type = I('type', 1);
        $page = I('page', 1);


        if($type == '1'){
            $join_on = 'f.friend_id = u.user_id';
            $where['f.user_id'] = $user_id;
            $field = 'friend_id user_id, head_pic, nickname, auth_video_status, twoway';
        } else {
            $join_on = 'f.friend_id = u.user_id';
            $where['f.friend_id'] = $user_id;
            $field = 'u.user_id, head_pic, nickname, auth_video_status, twoway';
        }

        $limit_start = ($page-1)*20;
        $lists = M('friend')->alias('f')
            ->join('users u', $join_on, 'left')
            ->where($where)
            ->order('f.id desc')
            ->field($field)
            ->limit($limit_start, 20)
            ->select();

        response_success($lists);
    }

    // 意见反馈
    public function feedback(){
        $data['user_id'] = I('user_id');
        $data['content'] = I('content');

        if($_FILES['image']){
            $FileLogic = new FileLogic();
            $uploadPath = UPLOAD_PATH.'feedback';
            $result = $FileLogic->uploadSingleFile('image', $uploadPath);
            if($result['status'] == '1'){
                $data['image'] = $result['fullPath'];
            } else {
                response_error('', '文件上传失败');
            }
        }

        if(M('feedback')->insert($data)){
            response_success('', '操作成功');
        } else {
            response_error('', '操作失败');
        }
    }

    // 获取用户认证视频连接
    public function getAuthVideoUrl(){
        $user_id = I('user_id');

        $video = M('users_auth_video')->where('user_id', $user_id)->field('auth_video_url')->find();
        if($video){
            response_success(array('video_url'=>$video['auth_video_url']));
        } else {
            response_error('', '该用户视频未认证');
        }
    }
}