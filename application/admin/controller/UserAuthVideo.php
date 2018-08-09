<?php


namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class UserAuthVideo extends Base {

    public function index(){
    	
        return $this->fetch();
    }
 }