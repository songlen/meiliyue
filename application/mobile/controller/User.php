<?php

namespace app\mobile\controller;


use think\db;

class User extends Base {


    public function comment(){
        $this->fetch();
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
            $field = 'user_id, head_pic, nickname, age, sex, uv.add_time, signature';
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

        if($this->request->isAjax()){
            response_success($lists);
        } else {


            $this->assign('type', $type);
            $this->assign('lists', $lists);
            return $this->fetch();
        }
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
            ->field('friend_id user_id, head_pic, nickname')
            ->select();

        if($this->request->isAjax()){
            response_success($lists);
        } else {

            $this->assign('lists', $lists);
            return $this->fetch();
        }
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
            $field = 'friend_id user_id, head_pic, nickname, twoway';
        } else {
            $join_on = 'f.friend_id = u.user_id';
            $where['f.friend_id'] = $user_id;
            $field = 'user_id, head_pic, nickname, twoway';
        }

        $limit_start = ($page-1)*20;
        $lists = M('friend')->alias('f')
            ->join('users u', $join_on, 'left')
            ->where($where)
            ->order('f.id desc')
            ->field($field)
            ->limit($limit_start, 20)
            ->select();

        if($this->request->isAjax()){
            response_success($lists);
        } else {

            $this->assign('lists', $lists);
            return $this->fetch();
        }
    }

    /**
     * [addFriend 添加好友]
     */
    public function addFriend(){

        if($this->reques->isPost()){
            $uuid = I('uuid');
            $user = M('users')->where('uuid', $uuid)
                ->field('user_id, head_pic, nickname, sex, age, signature')
                ->find();
            response_success($user);
        }
        return $this->fetch();
    }

    // 查看别人主页
    public function homePage(){

        return $this->fetch();
    }

    // 我的个人主页
    public function myHomePage(){

        return $this->fetch();
    }

    public function editHomePage(){
        return $this->fetch();
    }
}
