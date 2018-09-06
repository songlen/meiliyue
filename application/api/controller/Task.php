<?php

namespace app\api\controller;
use think\Db;

class Task {

	public function generateCongress(){
		$users = Db::name('users')->order('fansNum desc')->field('user_id, head_pic, nickname, sex, birthday, age, auth_video_status')->select(50);

        if(is_array($users) && is_array($users)){
            foreach ($users as &$item) {
                $item['age'] = getAge($item['birthday']);
            }
        }

        file_put_contents($filepath, "<?php \r\n return ".var_export($users, true).';');
	}
}