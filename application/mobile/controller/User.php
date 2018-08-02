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
        return $this->fetch();
    }

    /**
     * [friend 我的好友]
     * @return [type] [description]
     */
    public function friend(){

        return $this->fetch();
    }

    /**
     * [friend 关注和粉丝]
     * @param type 1 关注 2 粉丝
     * @return [type] [description]
     */
    public function attentionFans(){
       
        return $this->fetch();
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
