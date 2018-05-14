<?php
/**
 * Created by PhpStorm.
 * User: guoguangxiao
 * Date: 2017/9/18
 * Time: 23:28
 */

namespace app\api\model;

use think\Paginator;
class Order extends BaseModel
{
    protected $hidden = ['update_time','delete_time','user_id'];
    protected $autoWriteTimestamp = true;
     // 如果字段不是 create_time 可以自定成你的字段  这样框架就会把时间自动插入到你的字段里
     // 	                    你的字段
    // protected $createTime = 'create_at';
  
    // 读取器（获取器） 把json转成数组
    public function getSnapItemsAttr($value){
        if (empty($value)) {
            return null;
        }
        return json_decode($value);
    }
    // 读取器 把json转成数组
    public function getSnapAddressAttr($value){
        if (empty($value)) {
            return null;
        }
        return json_decode($value);
    }
    // 订单列表
    public static function getSummaryByUser($uid,$page=1,$size=15){
    	$pagingData = self::where('user_id','=',$uid)->order('create_time desc')->paginate($size,true,['page' => $page]);
    	return $pagingData;
    }
    

}