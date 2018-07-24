<?php

namespace app\mobile\controller;

use think\Db;
use think\Config;

 
class Dynamics extends Base {

    /**
     * [index 动态列表]
     * @param [range] $[range] [范围 1 同城 2 全网]
     * @return [type] [description]
     */
    public function index(){
        $user_id = 1;//I('user_id');
        $range = I('range', 1);
        $attention = I('attention', 0);
        $jizha = I('jizha', 0);

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

        $where['d.status'] = '2';
        $lists = Db::name('dynamics')->alias('d')
            ->join('users u', 'd.user_id=u.user_id', 'left')
            ->where($where)
            ->field('u.user_id, head_pic, nickname, u.sex, u.age, d.id dynamic_id, d.type, d.content, d.location, d.add_time, d.flower_num')
            ->order('d.id desc')
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

        if($this->request->isPost()){
            response_success($lists);
        } else {
            $this->assign('range', $range);
            $this->assign('lists', $lists);
            return $this->fetch();
        }
    }

    /**
     * [add description]
     */
    public function add(){
        $data['user_id'] = I('user_id');
        $data['type'] = I('type');
        $data['content'] = I('content');
        $data['location'] = I('location');
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
            response_success('', '操作成功');
        }
    }

    public function detail(){
        $id = I('id');

        $where['d.status'] = '2';
        $where['d.id'] = $id;
        $info = Db::name('dynamics')->alias('d')
            ->join('users u', 'd.user_id=u.user_id', 'left')
            ->where($where)
            ->field('u.user_id, head_pic, nickname, u.sex, u.age, d.id dynamic_id, d.type, d.content, d.location, d.add_time, d.flower_num')
            ->find();

        // 图片类型，取出图片
        if($info['type'] == '2'){
            $dynamics_image = M('dynamics_image')->where('dynamic_id', $info['dynamic_id'])->field('image')->select();
            $image = array();
            if(is_array($lists) && !empty($lists)){
                foreach ($dynamics_image as $v) {
                    $image[] = $v['image'];
                }
            }
            $info['image'] = $image;
        }
        // 视频
        if($info['type'] == '3'){
            $dynamics_image = M('dynamics_image')->where('dynamic_id', $info['dynamic_id'])->field('image')->find();
            
            $info['video_thumb'] = $dynamics_image['image'];
        }

        /*************** 获取查看此条动态的人 ****************/
        $viewers = Db::name('dynamics_viewer')->alias('dv')
            ->join('users u', 'dv.viewer_id=u.user_id', 'left')
            ->where('dynamic_id', $id)
            ->field('head_pic')
            ->order('dv.id desc')
            ->select();

        /********* 记录查看此条动态的人 **************/
        $viewer_id = 1;
        if($viewer_id != $info['user_id']){
            $is_exist = M('dynamics_viewer')->where(array('dynamic_id'=>$id, 'viewer_id'=>$viewer_id))->find();
            if(!$is_exist){
                M('dynamics_viewer')->insert(array('dynamic_id'=>$id, 'viewer_id'=>$viewer_id));
            }
        }
        /*********** 获取评论 ************/
        $comments = M('dynamics_comment')->alias('dc')
            ->join('users u', 'dc.commentator_id=u.user_id', 'left')
            ->where('dynamic_id', $id)
            ->field('head_pic, nickname, dc.content, dc.add_time')
            ->order('dc.id desc')
            ->select();


        $this->assign('info', $info);
        $this->assign('viewers', $viewers);
        $this->assign('comments', $comments);

        return $this->fetch();
    }
}