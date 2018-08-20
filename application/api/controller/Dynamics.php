<?php

namespace app\api\controller;

use think\Db;
use app\api\logic\FileLogic;
use app\api\logic\GeographyLogic;


class Dynamics extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}

    /**
     * [index 动态列表]
     * @param [range] $[range] [范围 1 同城 2 全网]
     * @return [type] [description]
     */
    public function index(){
        $user_id = I('user_id', 1);
        $range = I('range', 1);
        $attention = I('attention', 0);
        $jizha = I('jizha', 0);
        $page = I('page', 1);


        /************** 登录用户信息 ***************/
        $user = M('users')->where('user_id', $user_id)->field('city')->find();

        /************* 筛选条件 *************/
        $where = array(
            'd.status' => '2',
        );
        if($range == 1) $where['u.city'] = $user['city']; // 同城
       /**************** 关注 ***********/
        if($attention){
            $attention_users = M('friend')->where('user_id', $user_id)->field('friend_id')->select();
            $attention_uids = array();
            if(is_array($attention_users) && !empty($attention_users)){
                foreach ($attention_users as $a_user) {
                    $attention_uids[] = $a_user['friend_id'];
                }
            }
            $where['d.user_id'] = array('in', $attention_uids);
        }

        /************ 获取列表数据 *************/
        $limit_start = ($page-1)*10;
        $where['d.status'] = '2';
        $lists = Db::name('dynamics')->alias('d')
            ->join('users u', 'd.user_id=u.user_id', 'left')
            ->where($where)
            ->field('u.user_id, head_pic, nickname, u.sex, u.age, d.id dynamic_id, d.type, d.content, d.location, d.add_time, d.flower_num')
            ->order('d.id desc')
            ->limit($limit_start, 10)
            ->select();

        if(is_array($lists) && !empty($lists)){
            
            foreach ($lists as $k => &$item) {
                // 图片类型，取出图片
                if($item['type'] == '2'){
                    $dynamics_image = M('dynamics_image')->where('dynamic_id', $item['dynamic_id'])->field('image')->select();
                    $image = array();
                    if(is_array($lists) && !empty($lists)){
                        foreach ($dynamics_image as $v) {
                            $image[] = $v['image'];
                        }
                    }
                    $item['image'] = $image;
                }
                // 视频
                if($item['type'] == '3'){
                    $dynamics_image = M('dynamics_image')->where('dynamic_id', $item['dynamic_id'])->field('image')->find();
                    
                    $item['video_thumb'] = $dynamics_image['image'];
                }

                // 查看人数
                $item['viewer_count'] = M('dynamics_viewer')->where('dynamic_id', $item['dynamic_id'])->count();
                // 评论数 
                $item['comment_count'] = M('dynamics_comment')->where('dynamic_id', $item['dynamic_id'])->count();
            }
        }

        response_success($lists);
    }

    /**
     * [add description]
     */
    public function add(){
        $data['user_id'] = I('user_id');
        $data['type'] = I('type');
        $data['content'] = I('content');
        // $data['location'] = I('location');
        // $data['visible'] = I('visible');

        $data['status'] = '2';
        $data['add_time'] = time();

        /********************** 上传图片 *********************/
        if($_FILES['image'] && $data['type'] == '2'){
            $FileLogic = new FileLogic();
            $uploadPath = UPLOAD_PATH.'dynamics/image';
            $result = $FileLogic->uploadMultiFile('image', $uploadPath);
            if($result['status'] == '1'){
                $data['image'] = $result['image'];
            } else {
                response_error('', '文件上传失败');
            }
        }
        /************************ 上传视频 **********************/
        if($_FILES['video'] && $data['type'] == '3'){
            $FileLogic = new FileLogic();
            $uploadPath = UPLOAD_PATH.'dynamics/video';
            $result = $FileLogic->uploadSingleFile('video', $uploadPath);
            if($result['status'] == '1'){
                $data['video'] = $result['fullPath'];
            } else {
                response_error('', '文件上传失败');
            }
        }

        if(D('dynamics')->add($data)){
            response_success('', '操作成功');
        } else {
            response_error('', '操作失败');
        }
    }

    // 送小花朵
    public function giveFlower(){
        $dynamic_id = I('dynamic_id');
        $user_id = I('user_id');

        // 查询用户有没有花朵
        $user = M('users')->where('user_id', $user_id)->field('flower_num')->find();
        if($user['flower_num'] < 1) response_error('', '花朵数量不足');

        Db::name('users')->where('user_id', $user_id)->setDec('flower_num', 1);
        Db::name('dynamics')->where('id', $dynamic_id)->setInc('flower_num', 1);

        response_success('', '操作成功');
    }

    /**
     * [addComment 添加评论]
     */
    public function addComment(){
        $data['dynamic_id'] = I('dynamic_id');
        $data['commentator_id'] = I('commentator_id');
        $data['parent_id'] = I('parent_id', 0);
        $data['reply_user_id'] = I('reply_user_id', 0);
        $data['private'] = I('private', 0);
        $data['content'] = I('content');

        $data['add_time'] = time();

        if(M('dynamics_comment')->insert($data)){
            response_success('', '操作成功');
        } else {
            response_error('', '操作失败');
        }
    }

    public function getComment(){
        $dynamic_id = I('dynamic_id');


        /*********** 获取评论 ************/
        $comments = M('dynamics_comment')->alias('dc')
            ->join('users u1', 'dc.commentator_id=u1.user_id', 'left')
            ->join('users u2', 'dc.reply_user_id=u2.user_id', 'left')
            ->where('dynamic_id', $dynamic_id)
            ->field('u1.head_pic, u1.nickname, dc.id comment_id, commentator_id, dc.content, dc.add_time, u2.nickname reply_nickname, dc.parent_id')
            ->order('dc.id desc')
            ->select();
        
        $new_comments = array();
        if(is_array($comments) && !empty($comments)){
            foreach ($comments as $item) {
                $new_comments[$item['comment_id']] = $item;
            }
        }
        if(!empty($new_comments)){
            $new_comments = $this->_tree($new_comments);
        }

        response_success($new_comments);
    }

    /**
     * 生成目录树结构
     */
    private function _tree($data){

        $tree = array();
        foreach ($data as $item) {
               if(isset($data[$item['parent_id']])){
                  $data[$item['parent_id']]['sub'][] = &$data[$item['comment_id']];
               } else {
                  $tree[] = &$data[$item['comment_id']];
               }
        }

        return $tree;
    }
}