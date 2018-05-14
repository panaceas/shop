<?php 

namespace app\api\service;
use app\api\module\Order as OrderModel;
use app\api\service\Order as OrderService;
use app\lib\enum\OrderStatusEnum;
use think\Loader;
use think\Db;
Loader::import('WxPay.WxPay',EXTEND_PATH,'.Api.php');
// 继承微信SDK的回调类
class WxNotify extends \WxPayNotify
{
	// 重写NotifyProcess()方法 添加自己对返回成功的处理比如 减去库存等  
	// 这个方法会把微信返回xml数据转换成数组$data 具体看WxPay.Notify.php 
	public function NotifyProcess($data,&$msg){
		// 判断是否回调成功
		if ($data['result_code'] == "SUCCESS") {
			$orderno = $data['out_trade_no'];
			// 使用事务保证一次请求执行后在执行下一次
			Db::startTrans();
			try{														//数据库的锁操作
				$order = OrderModel::where('order_no','=',$orderno)->lock(true)->find();
				if ($order->status == 1) {
					$service = new OrderService();
					// 获取订单内的商品信息同时检测库存量
					$stockStatus = $service->checkOrderStock($order->id);
					// 库存量够的时候 更改订单的状态为2 减去库存
					if ($stockStatus['pass']) {
						$this->updateOrderStatus($order->id,true);
						$this->reduceStock($stockStatus);
					}else{
						// 库存量不够的时候 改为已支付，但库存不足 4
						$this->updateOrderStatus($order->id,false);
					}
				}
				Db:commit();
				return true;
			}catch(Exception $ex){
				//  记录日志 返回false 让微信在请求回调
				Db::rollback();
				Log::error($ex);
				return false;
			}
		}else{
			// 回调是失败返回true 不需要继续回调
			return true;
		}
	}
	// 修改库存
	private function reduceStock($stockStatus){
		foreach ($stockStatus['pStatusArray'] as $singlePStatus) {
			Product::where('id','=',$singlePStatus['id'])->setDec('stock',$singlePStatus['count']);
		}
	}
	// 更改状态
	private function updateOrderStatus($orderID,$success){
		$status = $success?OrderStatusEnum::PAID : OrderStatusEnum::PAID_BUT_OUT_OF;
		OrderModel::where('id','=',$orderID)->update(['status'=>$status]);

	}
	
}