<?php

namespace app\api\controller;
use think\Db;
use app\api\logic\UserLogic;
use app\api\logic\GeographyLogic;

class Index extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}
    
    /**
     * [index 首页在线列表]
     * @param tinyint $[order_type] [ 排序类型 1 最近活跃 2 离我最近]
     * @return [type] [description]
     */
    public function index(){

        $page = I('page', 1);
        $order_type = I('order_type', '1');
        $user_longitude = I('longitude'); // 排序为离我最近时传入
        $user_latitude = I('latitude'); // 排序为离我最近时传入
        $sex = I('sex');
        $province = I('province');
        $city = I('city');
        $auth_video_status = I('auth_video_status');
        $age_l = I('age_l');
        $age_r = I('age_r');
        $height_l = I('height_l');
        $height_r = I('height_r');
        $income = I('income');
        $satisfactory_parts = I('satisfactory_parts');

        $field = 'user_id, head_pic, nickname, active_time, longitude, latitude';

        // 排序
        if($order_type == '1'){
            $order = 'active_time desc';
        }
        if($order_type == '2'){
            $GeographyLogic = new GeographyLogic();
            // 计算500km 范围内的经纬度
            $around = $GeographyLogic->getAround($user_longitude, $user_latitude, 5000000);
            $where['longitude'] = array('BETWEEN', array($around['minLongitude'], $around['maxLongitude']));
            $where['latitude'] = array('BETWEEN', array($around['minLatitude'], $around['maxLatitude']));
            // sql 计算距离 并按距离排序
            $field .= ", ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($user_latitude*PI()/180-latitude*PI()/180)/2),2)+COS($user_latitude*PI()/180)*COS(latitude*PI()/180)*POW(SIN(($user_longitude*PI()/180-longitude*PI()/180)/2),2)))*1000) AS distince";
            $order = 'distince asc';
        }
        // 条件
        $where = array(
        	'is_lock' => '0',
            'is_line' => '1',
        );
        if($province && $city){
            $where['province'] = $province;
            $where['city'] = $city;
        }
        if($auth_video_status) $where['auth_video_status'] = $auth_video_status;
        if($auth_video_status) $where['auth_video_status'] = $auth_video_status;
        if($age_l && $age_r) $where['age'] = array(array('age', '>=', $age_l), array('age', '<', $age_r));
        if($height_l && $height_r) $where['height'] = array(array('height', '>=', $height_l), array('height', '<', $age_r));
        
        // 满意部位
        if($satisfactory_parts) $where['satisfactory_parts'] = $satisfactory_parts;

        $limit_start = ($page-1)*18;
        $users = M('users')->where($where)
            ->field($field)
            ->limit($limit_start, 18)
            ->order($order)
            ->select();


        response_success($users);
    }

    public function rocketNum(){
        $user_id = I('user_id');

        $userLogic = new UserLogic();
        $num = $userLogic->getRocketNum($user_id);

        response_success(array('num'=>$num));
    }

    public function setTop(){
        $user_id = I('user_id');

        // 查看用户是否是vip
        $user = M('users')->where('user_id', $user_id)->field('is_vip, rockets')->find();

        $userLogic = new UserLogic();
        $rocket_num = $userLogic->getRocketNum($user_id);
        // 当天已使用火箭数量
        $count = M('rocket_log')->where(array('user_id'=>$user_id, 'used_date'=>date('Y-m-d')))->count();

        if($count > $rocket_num){
            response_error('', '系统赠送的1个火箭已使用，VIP可每天置顶两次');
        } else {
            M('users')->where('user_id', $user_id)->update(array('active_time'=> time()));
            $rocket_log_data = array(
                'user_id' => $user_id,
                'used_date' => date('Y-m-d'),
                'used_time' => date('Y-m-d H:i:s'),
            );
            M('rocket_log')->insert($rocket_log_data);
            if($user['rockets']){
                M('user')->where('user_id', $user_id)->setDes('rockets');
            }
        }
        
        response_success();
    }
}