<?php 

namespace app\api\controller\v1;
use app\api\validate\AddressNew;
use app\api\service\Token as TokenService;
use app\api\model\User as UserModel;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserExpetion;
use think\Controller;
use app\lib\enum\ScopeEnum;
use app\lib\exception\ForbiddenException;
use app\lib\exception\TokenExpetion;

class Address extends BaseController
{	
	// 前置方法 checkPrimaryScope 在BaseController里
	protected $beforeActionList = [
		'checkPrimaryScope' =>['only' =>'createOrUpdateAddress']
	];
	
	// 创建或者更新收货地址
	public function createOrUpdateAddress(){
		$validate = new AddressNew();
		$validate->goCheck();
		//根据Token获取uid
		//根据uid来查找用户数据，判断用户是否存在 如果不存在抛出异常
		//获取用户从客户端提交来的地址信息
		//根据用户地址信息是否存在，从而判断是添加地址还是更新地址
		//
		$uid = TokenService::getCurrentUid();
		$user = UserModel::get($uid);
		if (!$user) {
			throw new UserExpetion();
		}
		// 获取过滤过的数据
		$dataArray = $validate->getDataByRule(input('post.'));
		//调用user模型下的address方法
		$userAddress = $user->address;
		if (!$userAddress) {
			// 新增 关联新增
			$user->address()->save($dataArray);
		}else{
			// 修改 关联更新时方法不加（） 详情看文档 搜索save -> 一对一关联
			$user->address->save($dataArray);
		}
		return json(new SuccessMessage(),201);
	}
}