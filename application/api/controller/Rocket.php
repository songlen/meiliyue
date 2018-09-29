<?php

namespace app\api\controller;
use think\Db;
use think\Config;
use app\api\logic\AlipayLogic;

class Rocket extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}


	// 下单
	public function placeOrder(){
		$user_id = I('user_id');

		$order_no = 'top'.date('YmdHis').$user_id;
		response_success(array('order_no'=>$order_no));
	}

	// 选择支付方式去支付
	public function topay(){
		$order_no = I('order_no');
		$paymentMethod = I('paymentMethod');

		/********* 判断订单信息 **************/
		$order = Db::name('vip_order')->where('order_no', $order_no)->find();
		if(empty($order)) response_error('', '该订单不存在');
		if($order['paystatus'] == 1) response_error('', '该订单已支付');

		$total_amount = 0.01; // 购买金币 1800

		/************** 获取订单签名字符串 **************/
		if($paymentMethod == 'alipay'){
			$notify_url = 'http://meiliyue.caapa.org/index.php/api/rocket/callback?paymentMethod=alipay';
			$AlipayLogic = new AlipayLogic($notify_url);
			$orderStr = $AlipayLogic->generateOrderStr($order_no, $total_amount, '购买金币', '购买金币');
			return $orderStr;
		}
	}

	// 购买vip后的支付回调接口
	public function Callback(){
		$order_no = input('post.out_trade_no');
		$trade_status = input('post.trade_status');	
		
		$user_id = substr($order_no, 17);
		// 回调后的业务流程
		if($trade_status == 'TRADE_SUCCESS'){
			// 启动事务
			Db::startTrans();
			try{
			    Db::name('users')->where('user_id', $user_id)->setDec('goldcoin', 1800);

			   	// 记录金币变动日志
				goldcoin_log($user_id, '-1800', 1, '购买火箭置顶');
			    
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			}

		}

		echo 'success';
	}

	public function IOSCallback(){
		$user_id = I('user_id');
		$level = I('level');

		// 计算到期日期
		$user = Db::name('users')->where('user_id', $user_id)->field('vip_expire_date')->find();
		$old_date = $user['vip_expire_date'] ? $user['vip_expire_date'] : date('Y-m-d');
		$enum = Config::load(APP_PATH.'enum.php', ture);
		$vip_config = $enum['vip'];
		$num = $vip_config[$level]['num'];
		$unit = $vip_config[$level]['unit'];
		$expire_date = date('Y-m-d', strtotime('+'.$num.$unit, strtotime($old_date)));

		Db::name('users')->where('user_id', $user_id)->update(array('level'=>$level, 'vip_expire_date'=>$expire_date));

		// ios没走下单接口，这里支付成功记录一下
		$order_no = $this->generateOrderno();
		$data = array(
			'order_no' => $order_no,
			'user_id' => $user_id,
			'level' => $level,
			'createtime' => time(),
		);
		Db::name('vip_order')->insert($data);

		response_success();
	}

	private function changeVip($order_no, $user_id, $level){

		Db::name('vip_order')->where('order_no', $order_no)->update(array('paystatus'=>'1', 'paytime'=>time()));
		// 计算到期日期
		$user = Db::name('users')->where('user_id', $user_id)->field('vip_expire_date')->find();
		$old_date = $user['vip_expire_date'] ? $user['vip_expire_date'] : date('Y-m-d');
		$enum = Config::load(APP_PATH.'enum.php', ture);
		$vip_config = $enum['vip'];
		$num = $vip_config[$level]['num'];
		$unit = $vip_config[$level]['unit'];
		$expire_date = date('Y-m-d', strtotime('+'.$num.$unit, strtotime($old_date)));

		Db::name('users')->where('user_id', $user_id)->update(array('level'=>$level, 'vip_expire_date'=>$expire_date));
	}

	private function generateOrderno(){
		$order_no = date('YmdHis').mt_rand(1000, 9999);

		$count = Db::name('vip_order')->where('order_no', $order_no)->count();

		if($count) $this->generateOrderno();
		return $order_no;
	}
}