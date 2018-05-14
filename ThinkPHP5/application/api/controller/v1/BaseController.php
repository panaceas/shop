<?php 

namespace app\api\controller\v1;

use think\Controller;
use app\api\service\Token as TokenService;

class BaseController extends Controller
{
	
	//用户和管理关都可以访问的权限 
	protected function checkPrimaryScope(){
		TokenService::needPrimaryScope();
		
	}
	// 只有用户才能访问的接口权限
	protected function checkExclusiveScope(){
		TokenService::needExclusiveScope();
		
	}
}