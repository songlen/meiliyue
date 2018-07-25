
<?php

function response_success($data=[], $msg=''){

	$result = array(
		'code' => 200,
		'data' => $data,
		'msg' => $msg,
	);

	json($result, 200)->send();
	exit;
}

function response_error($data=[], $msg=''){
	// header('content-type:application/json; charset=utf-8');
	$result = array(
		'code' => 400,
		'data' => $data,
		'msg' => $msg,
	);

	json($result, 200)->send();
	// echo json_encode($result);
	exit;
}

function generateOrderSn(){
	return date('YmdHis').mt_rand(1000,9999);
}


// 创建uuid
function generateUuid(){
    $uuid = mt_rand(10000000, 99999999);

    if(M('users')->where('uuid', $uuid)->count()){
        $this->generateUuid();
    }
    return $uuid;
}