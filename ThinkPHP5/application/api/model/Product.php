<?php 
namespace app\api\model;
use think\Model;
class Product extends BaseModel
{
	protected $hidden = ['main_img_id','pivot','update_time','delete_time','from','category_id','create_time'];
	public function	getMainImgUrlAttr($value,$data){
		return $this->prefixImg($value,$data);
	}

	public function imgs(){
		// 1对多用hasMary
		return $this->hasMany('ProductImage','product_id','id');
	}
	public function Properties(){
		
		return $this->hasMany('ProductProperty','product_id','id');
	}
	public static function getMostRecent($count){
		$products = self::limit($count)->order('create_time desc')->select();
		return $products;
	}
	public static function getProductByCategoryID($categotyID){
		$products = self::where('category_id','=',$categotyID)->select();
		return $products;
	}
	public static function getProductDetail($id){
		// 为了给imgs.下的imgUrl方法查出来的图片排序 所以写成这样  
		// 利用了一个闭包函数 给imgUrl进行了链式操作把图片排序
		//$query可以看一手册 直接搜就行 使用Query对象或闭包查询 意思差不多
		//self::with([])->with([]) 等于 self::with(['model1','model2'])
		//这里是在第一个with里使用了闭包函数
		$product = self::with([
			'imgs' => function($query){
				$query->with(['imgUrl'])
				->order('order','asc');
			}
		])
		->with(['Properties'])
		->find($id);
		return $product;
	}
}