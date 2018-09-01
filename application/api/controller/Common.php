<?php

namespace app\api\controller;

use think\Db;
use app\api\logic\FileLogic;


class Common extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}

    /**
     * [fileUpload 上传动态视频]
     * @param   $[file] [<文件名>]
     * @param [type] $[type] [<类型 动态视频:dynamic_video 动态图片: dynamic_image>]
     * @return [type] [description]
     */
    public function uploadFile(){
        $type = I('type');

        if( ! in_array($type, array('dynamic_image', 'dynamic_video'))) response_error('', '不被允许的类型');
        if(empty($_FILES)) response_error('文件不能为空');

        /************* 上传路径 ***************/        
        $uploadPath = UPLOAD_PATH.'files';
        if($type == 'dynamic_image') $uploadPath = UPLOAD_PATH.'dynamics/image';
        if($type == 'dynamic_video') $uploadPath = UPLOAD_PATH.'dynamics/video';
        
        $FileLogic = new FileLogic();
        $result = $FileLogic->uploadSingleFile('file', $uploadPath);
        if($result['status'] == '1'){
            $filepath = $result['fullPath'];
            $thumb = '';
            if($type == 'dynamic_video'){
                $thumb = $FileLogic->video2thumb($filepath);
            }
            response_success(array('filepath'=>$filepath, 'thumb'=>$thumb));
        } else {
            response_error('', '文件上传失败');
        }
    }
}