<?php

namespace app\api\controller;
use think\Db;
use think\Config;
use app\api\Logic\AlipayLogic;

class Vip extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}

	// 修改vip级别 0 普通会员 1 白金 2 黄金 3 vip
	// public function change(){
	// 	$user_id = I('user_id');
	// 	$level = I('level');

	// 	if(M('users')->where('user_id', $user_id)->setField('level', $level) !== false){
	// 		response_success('', '修改成功');
	// 	} else {
	// 		response_error('', '修改失败');
	// 	}
	// }


	// 下单
	public function placeOrder(){		

		$user_id = I('user_id');
		$level = I('level');

		$order_no = $this->generateOrderno();
		$data = array(
			'order_no' => $order_no,
			'user_id' => $user_id,
			'level' => $level,
			'createtime' => time(),
		);

		if(Db::name('vip_order')->insert($data)){
			response_success(array('order_no'=>$order_no));
		} else {
			response_error('', '下单失败');
		}
	}

	// 选择支付方式去支付
	public function topay(){
		$order_no = I('order_no');
		$paymentMethod = I('paymentMethod');

		/********* 判断订单信息 **************/
		$order = Db::name('vip_order')->where('order_no', $order_no)->find();
		if(empty($order)) response_error('', '该订单不纯在');
		if($order['paystatus'] == 1) response_error('', '该订单已支付');

		switch ($order['level']) {
			case '1':
				$total_amount = '0.01';
				break;
			
			case '2':
				$total_amount = '0.01';
				break;
			
			case '3':
				$total_amount = '0.01';
				break;
			
			case '4':
				$total_amount = '0.01';
				break;
		}

		/************** 获取订单签名字符串 **************/
		if($paymentMethod == 'alipay'){
			$AlipayLogic = new AlipayLogic();
			$orderStr = $AlipayLogic->generateOrderStr($order_no, $order);
			response_success(array('orderStr' => $orderStr));
		}
	}

	// 购买vip后的支付回调接口
	public function Callback(){
		$paymentMethod = input('post.paymentMethod');
		$order_no = input('post.out_trade_no');
		$trade_status = input('post.trade_status');

		if($paymentMethod == 'alipay'){
			$AlipayLogic = new AlipayLogic();
			$checkSign = $AlipayLogic->checkSign($order_no, $order);
		}

		if( ! $checkSign ) goto finish; //验签失败
		
		$order = Db::name('vip_order')->where('order_no', $order_no)->find();
		if(empty($order)) goto finish;
		if($order['paystatus'] == 1) goto finish;
		// 回调后的业务流程
		if($trade_status == 'SUCCESS'){
			$this->changeVip($order);
		}

		finish:
		echo 'success';
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

		Db::name('users')->where('user_id', $user_id)->udpate(array('level'=>$level, 'vip_expire_date'=>$expire_date));
	}

	private function generateOrderno(){
		$order_no = date('YmdHis').mt_rand(1000, 9999);

		$count = Db::name('vip_order')->where('order_no', $order_no)->count();

		if($count) $this->generateOrderno();
		return $order_no;
	}
}