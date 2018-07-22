<?php
/**
 * 短信验证码类
 */

namespace app\api\logic;
use think\Db;
use think\Controller;

class FileLogic extends Controller {

	// 多文件上传
	public function uploadMultiFile($field='file', $uploadPath){
		if (!file_exists($uploadPath)){
            mkdir($uploadPath, 0777, true);
        }

		$image = array();
		$files = $this->request->file($field);
        foreach ($files as $file) {
	        $info = $file->move($uploadPath, true);
	        if($info){
                $parentDir = '/'.date('Ymd'); // 系统默认在上传目录下创建了日期目录
                $fullPath = '/'.$uploadPath.$parentDir.'/'.$info->getFilename();
                $image[] = $fullPath;
            }   
        }
        return array('status' => '1', 'image'=>$image);
	}

	// 单一文件上传
	public function uploadSingleFile($field='file', $uploadPath){
		if (!file_exists($uploadPath)){
            mkdir($uploadPath, 0777, true);
        }

		$file = $this->request->file($field);
        $info = $file->move($uploadPath, true);
        if($info){
            $parentDir = '/'.date('Ymd'); // 系统默认在上传目录下创建了日期目录
            $fullPath = '/'.$uploadPath.$parentDir.'/'.$info->getFilename();
            return array('status'=>1, 'fullPath'=>$fullPath);
        } else {
        	return array('status' => -1);
        }
        
	}
}