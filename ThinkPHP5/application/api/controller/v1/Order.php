<?php 

namespace app\api\controller\v1;
use think\Controller;
use app\api\service\Token as TokenService;
use app\lib\enum\ScopeEnum;
use app\lib\exception\ForbiddenException;
use app\lib\exception\TokenException;
use app\lib\exception\OrderException;
use app\api\validate\OrderPlace;
use app\api\validate\IDMustBePostiveInt;
use app\api\service\Order as OrderService;
use app\api\model\Order as OrderModel;
use app\api\validate\PagingParameter;


class Order extends BaseController
{
	// 用户选择商品后，向API提交包含它所选择商品的相关信息
	// API在接收到信息后要检查订单相关商品的库存量
	// 有库存把订单数据存入数据库中=下单成功了，返回客户端消息，告诉客户端可以支付了
	// 调用我们的支付接口 进行支付
	// 还需要再次进行库存量检测 
	// 服务器这边就可以调用微信接口进行支付
	// 小程序根据服务器返回的结果拉起微信支付
	// 微信会返回给我们一个支付的结果（异步） 支付是否成功会异步通知小程序和服务器端 以不同的方式不同的路径
	// 成功：也需要进行库存量检测
	// 成功：进行库存量的扣除
	
	// 前置方法
	protected $beforeActionList = [
		'checkExclusiveScope' =>['only' =>'placeOrder'],
										// 两个方法之间要有空格 不然报错
		'checkPromaryScope' =>['only' =>'getDetail , getSummaryByUser']

	];
	public function getSummaryByUser($page=1,$size=15){
		(new PagingParameter())->goCheck();
		$uid = TokenService::getCurrentUid();
		$pagingOrders = OrderModel::getSummaryByUser($uid,$page,$size);
		// 如果要判断数据集是否为空，不能直接使用empty判断，而必须使用数据集对象的isEmpty方法
		if ($pagingOrders->isEmpty()) {
			return [
				'data' =>[],
				// getcurrentPage 分页的方法 返回当前页数 具体没整明白
				'current_page' => $pagingOrders->getcurrentPage()

			];
		}
		$data = $pagingOrders->hidden(['snap_items','snap_address','prepay_id'])->toArray();
		return [
			'data' =>$data,
			'current_page' => $pagingOrders->getcurrentPage()
		];

	}
	public function getDetail($id){
		(new IDMustBePostiveInt())->goCheck();
		$orderDetail = OrderModel::get($id);
		if (!$orderDetail) {
			throw new OrderException();
		}
		return $orderDetail->hidden(['prepay_id']);
	}
	public function placeOrder(){
		(new OrderPlace())->goCheck();
		// 如果获取的数据是数组要加/a   搜索变量修饰符
		$products = input('post.products/a');
		$uid = TokenService::getCurrentUid();
		$order = new OrderService();
		$status = $order->place($uid,$products);
		return $status;
	}

}