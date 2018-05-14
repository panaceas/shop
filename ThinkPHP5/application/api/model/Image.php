<?php 

namespace app\api\model;


use think\Model;
class Image extends BaseModel
{
	protected $hidden = ['id','update_time','delete_time','from'];
	// 获取器 ：get是固定的 Url是图片在数据库的字段 Attr也是固定的 $value 是框架自动调用读取器然后传入进来的  $data是其他字段
	public function	getUrlAttr($value,$data){
		return $this->prefixImg($value,$data);
	}
}