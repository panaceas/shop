<?php 

namespace app\api\model;
use think\Db;
use think\Exception;
use think\Model;
class Banner extends BaseModel
{	//设置要隐藏的字段 必须是protected $hidden 等于 $banner->hidden()
	protected $hidden = ['update_time','delete_time'];
	// protected $visible = ['update_time','delete_time'];
	
	// 模型默认对应的表是class名，下面是自定义模型对应的表
	// protected $table = 'category';

// banner表和banneritem表关联然后查出来的在和image关联
	public function items(){
		return $this->hasMany('BannerItem','banner_id','id');
	}
	public static function getBannerById($id){
		$banner = self::with(['items','items.img'])->find($id);
		return $banner;
		// $result = Db::query("select * from banner_item where banner_id =?",[$id]);
		//表达式
		// $result = Db::table('banner_item')
		// 	->where('banner_id','=',$id)
		// 	->select();
		// 	闭包
		// $result = Db::table('banner_item')
		// ->where(function ($query) use ($id){
		// 	$query->where('banner_id','=',$id);
		// })
		// ->select();
		// ORM Obeject Eelation Mapping 对象关系映射
		// 模型
		
	}
}