<?php 

namespace app\api\model;

class User extends BaseModel
{	
	// 一对一关系时 本表是主键的用hasOne  本表的外键的时候用belongsTo
	// $has~
	// 1、外键保存在关联表中；  
	// 2、保存时自动更新关联表的记录；  
	// 3、删除主表记录时自动删除关联记录。
	// $belongsTo
	// 1、外键放置在主表中；  
	// 2、保存时不会自动更新关联表的记录；  
	// 3、删除时也不会更新关联表的记录。

	public function address(){
		return $this->hasOne('UserAddress','user_id','id');
	} 
	public static function getByOpenId($openid){
		$user = self::where('openid','=',$openid)->find();
		return $user;
	}

}