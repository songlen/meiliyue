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
    public function attention(){
       
        return $this->fetch();
    }

    public function fans(){
        return $this->fetch();
    }

    /**
     * [addFriend 添加好友]
     */
    public function addFriend(){

        return $this->fetch();
    }

    // 查看别人主页
    public function homePage(){
        $user_id = I('user_id');
        $toUserId = I('toUserId');
        if($user_id == $toUserId){
            $this->redirect('user/myHomePage');
        }
file_put_contents('runtime/log/request.log', "\r\n homepage-$user_id-$toUserId", FILE_APPEND);
        return $this->fetch();
    }

    // 我的个人主页
    public function myHomePage(){
file_put_contents('runtime/log/request.log', "\r\n myhomepage", FILE_APPEND);
        return $this->fetch();
    }

    public function editHomePage(){
        return $this->fetch();
    }
}
