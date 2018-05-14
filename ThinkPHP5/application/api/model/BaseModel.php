<?php 

namespace app\api\model;
use think\Model; 


class BaseModel extends Model
{
		// 获取器 ：get是固定的 Url是图片在数据库的字段 Attr也是固定的 $value 是框架自动调用读取器然后传入进来的  $data是其他字段
	// public function getUrlAttr($value,$data){
	// 	$finalUrl = $value;
	// 	if ($data['from'] == 1) {
	// 		// 把配置文件里的路径拼上  
	// 		$finalUrl = config('setting.img_prefix').$value;
	// 	}
	// 	return $finalUrl;
		
	// }
	
	//这是自定义的方法 不是获取器
	protected function prefixImg($value,$data){
		$finalUrl = $value;
		if ($data['from'] == 1) {
			// 把配置文件里的路径拼上  
			$finalUrl = config('setting.img_prefix').$value;
		}
		return $finalUrl;
		
	}
}