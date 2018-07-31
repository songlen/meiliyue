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
               vendor('Alchemy.BinaryDriver.ProcessRunner');
               vendor('Alchemy.BinaryDriver.ProcessRunnerInterface');
               vendor('Alchemy.BinaryDriver.ProcessRunner');
               vendor('Alchemy.BinaryDriver.ProcessBuilderFactoryInterface');
               vendor('Alchemy.BinaryDriver.ProcessBuilderFactory');
               vendor('Alchemy.BinaryDriver.ConfigurationInterface');
               vendor('Alchemy.BinaryDriver.Configuration');
               vendor('Alchemy.BinaryDriver.EventEmitterInterface');
               vendor('Alchemy.BinaryDriver.ProcessRunnerAwareInterface');
               vendor('Alchemy.BinaryDriver.ProcessBuilderFactoryAwareInterface');
               vendor('Alchemy.BinaryDriver.ConfigurationAwareInterface');
               vendor('Alchemy.BinaryDriver.BinaryInterface');
               vendor('Alchemy.BinaryDriver.AbstractBinary');
               vendor('Doctrine.Common.Cache.MultiPutCache');
               vendor('Doctrine.Common.Cache.MultiGetCache');
               vendor('Doctrine.Common.Cache.ClearableCache');
               vendor('Doctrine.Common.Cache.FlushableCache');
               vendor('Doctrine.Common.Cache.Cache');
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