<?php 
namespace app\api\model;

use think\Model;
class Theme extends BaseModel
{
	protected $hidden = ['topic_img_id','head_img_id','update_time','delete_time'];
	public function topImg(){
		return $this->belongsTo('Image','topic_img_id','id');
	}
	public function headImg(){
		return $this->belongsTo('Image','head_img_id','id');
	}
	public function products(){
		// 这里theme要和product关联 但是他们不存在关联字段 就要用到能关联他们的表theme_product 没有直接关联 第二个参数要填存在他们关联关系的表名 然后两个参数是两个表的关联关系字段
		return $this->belongsToMany('Product','theme_product','product_id','theme_id');
	}
	public static function getThemeWithProducts($id){
		$theme = self::with('products,topImg,headImg')->find($id);
		return $theme;
	}
}