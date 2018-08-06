<?php

namespace app\mobile\controller;

use think\Db;
use think\Config;

 
class Invite extends Base {
	public function index(){
        $user_longitude = I('longitude');
        $user_latitude = I('latitude');
        $type = I('type');
        $sex = I('sex', 0);
        $province = I('province');
        $city = I('city');
        $auth_video = I('auth_video');
        /************************ 要查询的字段 ***************************/
        $field = 'u.user_id, head_pic, auth_video_status, nickname, u.sex, u.age, i.id invite_id, i.title, i.description, i.time, i.place, image';
        /************************ 排序 ***************************/
        $order_type = I('order_type', 1);
        if($order_type == 1) $order = 'add_time desc';
        if($order_type == 2) {
            $where['time'] = array('>=', date('Y-m-d'));
            $order = 'time asc';
        }
        if($order_type == 3) {
            $GeographyLogic = new GeographyLogic();
            // 计算500km 范围内的经纬度
            $around = $GeographyLogic->getAround($user_longitude, $user_latitude, 5000000);
            $where['u.longitude'] = array('BETWEEN', array($around['minLongitude'], $around['maxLongitude']));
            $where['u.latitude'] = array('BETWEEN', array($around['minLatitude'], $around['maxLatitude']));
            // sql 计算距离 并按距离排序
            $field .= ", ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($user_latitude*PI()/180-i.latitude*PI()/180)/2),2)+COS($user_latitude*PI()/180)*COS(i.latitude*PI()/180)*POW(SIN(($user_longitude*PI()/180-i.longitude*PI()/180)/2),2)))*1000) AS distince";
            $order = 'distince asc';
        }

        /************************ 筛选条件 ***************************/
        if($type) $where['type'] = $type;
        if($sex) $where['u.sex'] = ($sex == 1) ? 1 : 2;
        if($province) $where['i.province'] = $province;
        if($city) $where['i.city'] = $city;
        if($auth_video) $where['u.auth_video_status'] = 1;

        $page = I('page', 1);
        $limit_start = ($page-1)*10;

        $where['i.status'] = '2';
        $lists = Db::name('invite')->alias('i')
            ->join('users u', 'i.user_id=u.user_id', 'left')
            ->where($where)
            ->field($field)
            ->order($order)
            ->limit($limit_start, 10)
            ->select();

        if(!empty($lists)){
            
            foreach ($lists as $k => &$item) {
                // 反序列化图片
                $item['image'] = $item['image'] ? unserialize($item['image']) : '';

                // 计算用户和发布者之间的距离，sql计算出来的是米 这里转换成 km
                $item['distince'] = round($item['distince']/1000, 2); 
            }
        }
        

        if($this->request->isAjax()){
        	response_success($list);
        } else {
	        $this->assign('lists', $lists);
	        return $this->fetch();
        }
    }

    public function add(){
        if($this->request->isPost()){
        	$data['user_id'] = I('user_id');
        	$data['user_sex'] = I('user_sex');
        	$data['type'] = I('type');
        	$data['title'] = I('title');
        	$data['description'] = I('description');
        	$data['time'] = I('time');
        	$data['province'] = I('province');
        	$data['city'] = I('city');
        	$data['place'] = I('place');
        	$data['longitude'] = I('longitude');
        	$data['latitude'] = I('latitude');
        	$data['object'] = I('object');
        	$data['pay'] = I('pay');
        	$data['is_jiesong'] = I('is_jiesong');
        	$data['with_confidante'] = I('with_confidante');

        	$data['add_time'] = time();

        	if($_FILES['file']){
        	    $FileLogic = new FileLogic();
        	    $uploadPath = UPLOAD_PATH.'invite/';
        	    $result = $FileLogic->uploadMultiFile('file', $uploadPath);
        	    if($result['status'] == '1'){
        	        $image = $result['image'];
        	    } else {
        	        response_error('', '图片上传失败');
        	    }

        	    $data['image'] = serialize($image);
        	}
        	M('invite')->insert($data);
        	response_success('', '操作成功');
        } else {
        	$type = I('type');
        	$enum = Config::load(APP_PATH.'enum.php');

        	$this->assign('inviteTypeEnum', $enum['invite_type']);
        	$this->assign('type', $type);
        	return $this->fetch();
        }
    }

    public function detail(){

    	return $this->fetch();
    }
}