<?php 

namespace app\api\service;
use app\api\service\Order as OrderService;
use app\api\model\Order as OrderModel;
use app\api\service\Token;
use app\lib\enum\OrderStatusEnum;
use app\lib\exception\OrderException;
use app\lib\exception\TokenException;
use think\Exception;
use think\Loader;
use think\Log;

//    extend/WxPay/WxPay.Api.php
Loader::import('WxPay.WxPay',EXTEND_PATH,'.Api.php');
class Pay 
{
	
	private $orderID;
	private $orderNO;
	public function __construct($orderID){
		if (!$orderID) {
			throw new Exception('订单号不允许为NULL');
		}
		$this->orderID = $orderID;
	}

	public function pay(){
		// 订单号可能根本不存在
		// 订单号确实存在的，但是 订单号和当前用户是不匹配的
		// 订单有可能已经被支付过了
		//进行库存量检测
		$this->checkOrderValid();
		$orderService = new OrderService();
		$status = $orderService->checkOrderStock($this->orderID);
		// 如果库存量不足返回商品状态
		if (!$status['pass']) {
			return $status;
		}
        return $this->makeWxPreOrder($status['orderPrice']);
	}
	private function makeWxPreOrder($totalPrice){
		$openid = Token::getCurrentTokenVar('openid');
		if (!$openid) {
			throw new TokenException();
		}
		// 实例化微信sdk类 没有命名空间的类 实例化的时候要加\
		$wxOrderData = new \WxPayUnifiedOrder();

		//商户订单号
		$wxOrderData->SetOut_trade_no($this->orderNO);
		// 小程序的微信支付标示
		$wxOrderData->SetTrade_type('JSAPI');
		// 金额
		$wxOrderData->SetTotal_fee($totalPrice*100);
		//商品描述
		$wxOrderData->SetBody('部落零售');
		// openid
		$wxOrderData->SetOpenid($openid);
		// 回调地址
		$wxOrderData->SetNotify_url(config('secure.pay_back_url'));
		return $this->getPaySignature($wxOrderData);
	}
	// 调用支付
	private function getPaySignature($wxOrderData){
		$wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
		// return $wxOrder;
		 // 失败时不会返回result_code
        if($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] !='SUCCESS'){
            Log::record($wxOrder,'error');
            Log::record('获取预支付订单失败','error');
           // throw new Exception('获取预支付订单失败');
        }
        $this->recordPreOrder($wxOrder);
        $signature = $this->sign($wxOrder);
        return $signature;
	}
	// 调用SDK的方法生成签名 并获取接口需要的参数 WxPay.Data.php 
	private function sign($wxOrder){
		$jsApiPayData = new \WxPayJsApiPay();
		// 小程序ID
		$jsApiPayData->SetAppid(config('wx_app'));
		// 时间戳 字符串类型
		$jsApiPayData->SetTimeStamp((string)time());
		// 生成随机数
		$rand = md5(time().mt_rand(0,1000));
		// 随机串
		$jsApiPayData->SetNonceStr($rand);
		// 数据包 统一下单接口返回的 prepay_id 
		// $jsApiPayData->SetPackage('prepay_id='.$wxOrder['prepay_id']);
		// 不能调用预订单接口prepay_id用1代替 正确的是上面的
		$jsApiPayData->SetPackage('prepay_id='.'1');
		// 签名方式
		$jsApiPayData->SetSignType('md5');
		// 生成签名
		$sign = $jsApiPayData->MakeSign();
		// 获取设置完的值   返回接口需要的参数 
		$rawValues = $jsApiPayData->GetValues();
		$rawValues['paySign'] = $sign;
		// 把数组中appId去掉  不返回
		unset($rawValues['appId']);
		return $rawValues;

	}
	// 统一下单接口返回的 prepay_id  存入数据库 
	private function recordPreOrder($wxOrder){
		// OrderModel::where('id','=',$this->orderID)->update(['prepay_id'=>$wxOrder['prepay_id']]);
		// // 不能调用预订单接口prepay_id用1代替
		OrderModel::where('id','=',$this->orderID)->update(['prepay_id'=>1]);
	}
	// 验证订单的信息
	private function checkOrderValid(){
		$order = OrderModel::where('id','=',$this->orderID)->find();
		// 订单号是否存在
		if (!$order) {
			throw new OrderException();
		}
		// 订单号与用户是否匹配
		if (!Token::isValidOperate($order->user_id)) {
			throw new TokenException([
				'msg' =>'订单与用户不匹配',
				'errorCode' => 10003
				]);
		}
		// 是否支付过
		if ($order->status != OrderStatusEnum::UNPAID) {
			throw new OrderException([
					'msg' =>'订单已支付过啦',
					'errorCode' =>80003,
					'code' =>400
				]);
		}
		$this->orderNO = $order->order_no;
		return true;
	}


}