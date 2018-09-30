<?php

namespace app\api\controller;
use think\Db;
use think\Config;
use app\api\logic\AlipayLogic;

class Goldcoin extends Base {

	public function __construct(){
		// 设置所有方法的默认请求方式
		$this->method = 'POST';

		parent::__construct();
	}


	// 下单
	public function placeOrder(){

		$user_id = I('user_id');
		$goldcoin_id = I('goldcoin_id');

		$goldcoin = M('goldcoin')->where(array('id'=>$goldcoin_id, 'is_delete'=>0))->find();
		if(empty($goldcoin)) response_error('', '该金币商品不存在');

		$order_no = $this->generateOrderno();
		$data = array(
			'order_no' => $order_no,
			'user_id' => $user_id,
			'goldcoin_id' => $goldcoin_id,
			'num' => $goldcoin['num'],
			'give_num' => $goldcoin['give_num'],
			'price' => $goldcoin['price'],
			'createtime' => time(),
		);

		if(Db::name('goldcoin_order')->insert($data)){
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
		$order = Db::name('goldcoin_order')->where('order_no', $order_no)->find();
		if(empty($order)) response_error('', '该订单不存在');
		if($order['paystatus'] == 1) response_error('', '该订单已支付');

		$total_amount = 0.01;//$order['price'];

		/************** 获取订单签名字符串 **************/
		if($paymentMethod == 'alipay'){
			$notify_url = 'http://meiliyue.caapa.org/index.php/api/goldcoin/callback?paymentMethod=alipay';
			$AlipayLogic = new AlipayLogic($notify_url);
			$orderStr = $AlipayLogic->generateOrderStr($order_no, $total_amount, '购买金币', '购买金币');
			return $orderStr;
		}
	}

	// 购买vip后的支付回调接口
	public function Callback(){
		$paymentMethod = input('post.paymentMethod');
		$order_no = input('post.out_trade_no');
		$trade_status = input('post.trade_status');

		if($paymentMethod == 'alipay'){
			$AlipayLogic = new AlipayLogic();
			//验签失败

			/*$param = $_POST;
			$param['fund_bill_list'] = html_entity_decode($param['fund_bill_list']);
			$_POST = $param;
			if( ! $AlipayLogic->checkSign()) die('error');*/
		}
		
		
		
		$order = Db::name('goldcoin_order')->where('order_no', $order_no)->find();
		if(empty($order)) goto finish;
		if($order['paystatus'] == 1) goto finish;
		// 回调后的业务流程
		if($trade_status == 'TRADE_SUCCESS'){
			$this->operation($order_no, $order['user_id']);
		}

		finish:
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
		Db::name('goldcoin_order')->insert($data);

		response_success();
	}

	private function operation($order_no, $user_id){

		// 启动事务
		Db::startTrans();
		try{
			
		    // 记录赠送
		    $goldcoin_order = M('goldcoin_order')->where('order_no', $order_no)->find();

            // 加金币
		    Db::name('users')->where('user_id', $user_id)->setInc('goldcoin', $goldcoin_order['price']);
		   
		   	// 记录金币变动日志
			goldcoin_log($user_id, "+{$giftinfo['price']}", 2, '购买金币', $goldcoin_order['id']);

		    // 提交事务
		    Db::commit();

		    response_success('', '购买成功');
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();

		    response_error('', '购买失败');
		}
	}

	private function generateOrderno(){
		$order_no = date('YmdHis').mt_rand(1000, 9999);

		$count = Db::name('goldcoin_order')->where('order_no', $order_no)->count();

		if($count) $this->generateOrderno();
		return $order_no;
	}
}