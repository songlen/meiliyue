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
        switch ($type) {
            case 'dynamic_image':
                $uploadPath = UPLOAD_PATH.'dynamics/image';
                break;
            case 'dynamic_video':
                $uploadPath = UPLOAD_PATH.'dynamics/video';
                break;
            
            default:
                $uploadPath = UPLOAD_PATH.'files';
                break;
        }

        if(empty($_FILES)) response_error('文件不能为空');

        $FileLogic = new FileLogic();
        $result = $FileLogic->uploadSingleFile('file', $uploadPath);
        if($result['status'] == '1'){
            response_success(array('filepath'=>$result['fullPath']));
        } else {
            response_error('', '文件上传失败');
        }
    }
}