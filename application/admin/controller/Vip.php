<?php


namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
use app\api\logic\MessageLogic;

class Vip extends Base {

    public function index(){
    	$searchtype = I('searchtype');
        $keyword = I('keyword', '');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $status = I('status');

        // 搜索条件
        $where = array();
        if($keyword){
            if($searchtype == 'nickname') $where['nickname'] = array('like', "%$keyword%");
            if($searchtype == 'uuid') $where['uuid'] = $keyword;
        }

        if($start_time && $end_time){
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);

            $where['paytime'] = array('BETWEEN', array($start_time, $end_time));

            $start_time = date('Y-m-d H:i:s', $start_time);
            $end_time = date('Y-m-d H:i:s', $end_time);
        }


        $count = M('vip_order')->alias('vo')
            ->join('users u', 'vo.user_id=u.user_id', 'left')
            ->where($where)
            ->where('paystatus', 1)
            ->count();

        $Page = new Page($count,1);
        $show = $Page->show();

        $lists = M('vip_order')->alias('vo')
            ->where($where)
            ->join('users u', 'vo.user_id=u.user_id', 'left')
            ->where('paystatus', 1)
            ->order('id desc')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->field('u.user_id, u.nickname, u.vip_expire_date, u.uuid, vo.id, vo.level, vo.paystatus, vo.paytime')
            ->select();

        // 统计vip购买数量
        $total_amount = M('vip_order')->alias('vo')
            ->join('users u', 'vo.user_id=u.user_id', 'left')
            ->where($where)
            ->where('paystatus', 1)
            ->sum('amount');

                           
        $show = $Page->show();
        $this->assign('lists',$lists);
        $this->assign('show', $show);
        $this->assign('searchtype', $searchtype);
        $this->assign('keyword', $keyword);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('total_amount', $total_amount);
        return $this->fetch();
    }
 }