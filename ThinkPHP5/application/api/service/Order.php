<?php 

namespace app\api\service;
use app\lib\exception\OrderException;
use app\lib\exception\UserException;
use app\api\model\Product;
use app\api\model\UserAddress;
use app\api\model\OrderProduct;
use think\Db;


class Order
{	
	// 订单的商品列表，也就是客户端传递过来的products参数
	protected $oProducts;
	// 真实的商品信息（包括库存量）
	protected $Products;
	protected $uid;

	public function place($uid,$oProducts){
		// $oProducts 和 $Products 做对比
		// products从数据库查出来
		// 客户端传递过来
		$this->oProducts = $oProducts;
		// 真实的商品信息
		$this->Products = $this->getProductsByOrder($oProducts);
		$this->uid = $uid;
		$status = $this->getOrderStatus();
		if (!$status['pass']) {
			$status['order_id'] = -1;
			return $status;
		}
		//开始创建订单
		$orderSnap = $this->snapOrder($status);
		$order = $this->createOrder($orderSnap);
		$order['pass'] = true;
		return $order;
	}
	private function createOrder($snap){
		// 事务开始
		Db::startTrans();
	    try{
	    	// 给order表添加数据 
            $orderNo = $this->makeOrderNo();
            $order = new \app\api\model\Order();

            // 用户id
            $order->user_id = $this->uid;
            // 订单号
            $order->order_no = $orderNo;
            // 订单总价
            $order->total_price = $snap['orderPrice'];
            // 订单内总数量
            $order->total_count = $snap['totalCount'];
            // 第一个商品图片
            $order->snap_img = $snap['snapImg'];
            // 第一个商品name
            $order->snap_name = $snap['snapName'];
            // 地址
            $order->snap_address = $snap['snapAddress'];
            // 订单内的商品信息
            $order->snap_items = json_encode($snap['pStatus']);
            $order->save();

            // 订单id
            $orderID = $order->id;
            $create_time = $order->create_time;
            // 给每个数组添加上对应的订单id
            foreach($this->oProducts as &$p){
                $p['order_id'] = $orderID;
            }
            // 给orderproduct表添加数据  按客户端传过来的值添加 每个数组一条
            // 与product表形成关联
            $orderProduct = new OrderProduct();
            $orderProduct->saveAll($this->oProducts);
            // 事务结束
            Db::commit();
            // 返回订单信息
            return [
                'order_no' => $orderNo,
                'order_id' => $orderID,
                'create_time' => $create_time
            ];
        }catch(Exception $ex){
        	Db::rollback();
	        throw $ex;
        }
    }
    public static function makeOrderNo()
    {
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $orderSn =
            $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date(
                'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
                '%02d', rand(0, 99));
        return $orderSn;
    }
	// 生成订单快照
	private function snapOrder($status){
		$snap = [
			'orderPrice' => 0,
			'totalCount' => 0,
			'pStatus' => [],
			'snapAddress' => null,
			'snapName' => '',
			'snapImg' => ''
		];

		$snap['orderPrice'] = $status['orderPrice'];
		$snap['totalCount'] = $status['totalCount'];
		$snap['pStatus'] = $status['pStatusArray'];
		$snap['snapAddress'] = json_encode($this->getUserAddress());
		$snap['snapName'] = $this->products[0]['name'];
		$snap['snapImg'] = $this->products[0]['main_img_url'];
		if (count($this->products) > 1) {
			$snap['snapName'] .= '等';
		}
		return $snap;
	}
	// 获取地址
	private function getUserAddress(){
		$userAddress = UserAddress::where('user_id','=',$this->uid)->find();
		if (!$userAddress) {
			throw new UserException([
				'msg' =>'用户收货地址不存在，下单失败',
				'errorCode' => 60001
			]);
			
		}
		return $userAddress->toArray();
	}
	// 外部调用获取订单整体状态的方法
	public function checkOrderStock($orderID){
		// 根据订单ID查找出订单的商品数组 OrderProduct表
		$oProducts = OrderProduct::where('order_id','=',$orderID)->select();
		$this->oProducts = $oProducts;
		// 根据商品数据拿到数据库里的商品 
		$this->products = $this->getProductsByOrder($oProducts);
		$status = $this->getOrderStatus();
		return $status;
	}
	// 获取订单整体的状态
	private function getOrderStatus(){
		// 订单整体的状态
		$status = [
			'pass' => true,
			'orderPrice' =>0,
			'totalCount' =>0,
			'pStatusArray' =>[]
		];
		foreach ($this->oProducts as $oProducts) {
			// 获取商品数据 库存量 价格。。。 
			$pStatus = $this->getProductStatus($oProducts['product_id'],$oProducts['count'],$this->products);
			if (!$pStatus['haveStock']) {
				$status['pass'] = false;
			}
			$status['orderPrice'] += $pStatus['totalPrice'];
			$status['totalCount'] += $pStatus['count'];
			array_push($status['pStatusArray'],$pStatus);
		}
		return $status;
	}
	// 获取每类商品的状态
	private function getProductStatus($oPIDs,$oCount,$products){
		$pIndex = -1;
		// 每类商品的状态
		$pStatus = [
			'id' => null,
			'haveStock' =>false,
			'count' =>0,
			'name' =>'',
			'totalPrice' => 0
		];
		// 传过来的商品在不在数据库里
		for ($i=0; $i < count($products); $i++) { 
			if ($oPIDs == $products[$i]['id']) {
				$pIndex = $i;
			}
		}
		// 没查到商品 抛出 错误
		if ($pIndex == -1) {
			// 客户端传递的product_id有可能根本不存在
			throw new OrderException([
				'msg' =>'id为'.$oPIDs.'的商品不存在，创建订单失败'
				]);
		}else{
			$product = $products[$pIndex];
			$pStatus['id'] = $product['id'];
			$pStatus['name'] = $product['name'];
			$pStatus['count'] = $oCount;
			// 单品价格总和            单价               * 数量
			$pStatus['totalPrice'] = $product['price'] * $oCount;
			if ($product['stock'] - $oCount >= 0) {
				// 库存量是否够
				$pStatus['haveStock'] = true;
				
			}
			return $pStatus;
		}
	}
	// 根据订单信息查找真实的商品信息
	private function getProductsByOrder($oProducts){
		// 把订单中的商品id取到一个数组 然后查找真实的商品信息
		$oPIDs = [];
		foreach ($oProducts as $item) {
			array_push($oPIDs,$item['product_id']);
		}
		// 使用数组获取多个数据 
		$products = Product::all($oPIDs)->visible(['id','price','stock','name','main_img_url'])->toArray();
		return $products;
	}

}