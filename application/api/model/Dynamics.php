<?php
namespace app\api\model;

use think\Model;

class Dynamics extends Model {

	public function add($data){
		$dynamicsModel = new Dynamics;
		$dynamicsModel->save($data);
		$insert_id = $dynamicsModel->id;
        if($insert_id){
            // 插入图片表
            if(is_array($data['image']) && !empty($data['image'])){
                foreach ($data['image'] as $item) {
                    M('dynamics_image')->insert(array('dynamic_id'=>$insert_id, 'image'=>$item));
                }
            }
            // 保存视频路径
            if($data['video']){
               $video = $data['video'];
               vendor('Doctrine.Common.Cache.CacheProvider');
               vendor('Doctrine.Common.Cache.ArrayCache');
                // 创建视频缩略图
                $ffmpeg = \FFMpeg\FFMpeg::create(array(
                    // 'ffmpeg.binaries'  => VENDOR_PATH.'php-ffmpeg/bin/ffmpeg.exe',
                    // 'ffprobe.binaries' => VENDOR_PATH.'php-ffmpeg/bin/ffprobe.exe',
                    'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                    'ffprobe.binaries' => '/usr/bin/ffprobe',
                ));

                $videoObj = $ffmpeg->open(ROOT_PATH.$video);
                $pos = strpos($video, '.');
                $video_pic = substr($video, 0, $pos).'.jpg';
                $videoObj
                    ->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(0))
                    ->save(ROOT_PATH.$video_pic);
                M('dynamics_image')->insert(array('dynamic_id'=>$insert_id, 'image'=>$video_pic, 'video'=>$video));
            }
            return true;
        } else {
        	return false;
        }
	}
}