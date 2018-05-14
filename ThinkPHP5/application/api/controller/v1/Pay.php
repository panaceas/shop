<?php 
namespace app\api\controller\v1;
use app\api\controller\v1\BaseController;
use app\api\validate\IDMustBePostiveInt;
use app\api\service\Pay as PayService;
use app\api\service\WxNotify;

//用户点击下单 调用服务器的下单接口 然后服务器调用微信的预订单接口 微信返回预订单信息  返回成功后 服务器整理数据 签名等 返回给小程序 小程序调用小程序的微信支付接口 
class Pay extends BaseController
{	
	// 前置方法 判断是不是用户
	protected $beforeActionList = [
		'checkPrimaryScope' =>['only' =>'getPreOrder']
	];

	public function getPreOrder($id =''){
		(new IDMustBePostiveInt())->goCheck();
		$pay = new PayService($id);
		return $pay->pay();
	}
	public function receiveNotify(){
		// 1.检测库存量
		// 2.更新这个订单的status状态
		// 3.减库存
		// 4.如果成功出来，我们返回微信成功处理的信息，否则，我们需要返回没有成功处理
		// 
		// 特点：post：xml格式：url上不会携带参数
		$notify = new WxNotify();
		// SDK的回调入口
		$notify->Handle();
	}
	
}