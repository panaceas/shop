<?php 
namespace app\api\service;
use think\Request;
use think\Cache;
use app\lib\exception\TokenException;
use app\lib\enum\ScopeEnum;
use app\lib\exception\ForbiddenException;
class Token
{
	//组成token
	public static function generateToken(){
		// 32个字符随机组成一组随机字符串
		$randChars = getRandChar(32);
		//得到请求此php脚本时的时间戳
		//$_SERVER["REQUEST_TIME"] 得到请求开始时的时间戳
		$timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
		//salt 盐
		$salt = config('secure.token_salt');
		//用三组字符串，进行MD5加密
		return md5($randChars.$timestamp.$salt);
	}
	// 缓存中是微信返回的 openid、msg 等   根据$key 取不同的值
	public static function getCurrentTokenVar($key){
		$token = Request::instance()->header('token');
		$vars = Cache::get($token);
		if (!$vars) {
			throw new TokenException();
		}else{
			if (!is_array($vars)) {
				$vars = json_decode($vars,true);
			}
			if (array_key_exists($key, $vars)) {
				return $vars[$key];
			}else{
				throw new Exception("尝试获取的Token变量并不存在");
				
			}
			
		}
	}
	// 取uid
	public static function getCurrentUid(){
		//token 
		$uid = self::getCurrentTokenVar('uid');
		return $uid;
	}
	//用户和管理关都可以访问的权限
	public static function needPrimaryScope(){

		$scope = self::getCurrentTokenVar('scope');
		if ($scope) {
			if ($scope >= ScopeEnum::User) {
				return true;
			}else{
				throw new ForbiddenException();
			}
		}else{
			throw new TokenException();
		}
	}
	// 只有用户才能访问的接口权限
	public static function needExclusiveScope(){
		$scope = self::getCurrentTokenVar('scope');
		if ($scope) {
			if ($scope == ScopeEnum::User) {
				return true;
			}else{
				throw new ForbiddenException();
			}
		}else{
			throw new TokenException();
		}
	}
	public static function isValidOperate($checkedUID){
		if (!$checkedUID) {
			throw new Exception('检查UID时必须传入一个被检查的UID');
		}
		$currentOperateUID = self::getCurrentUid();
		if ($currentOperateUID == $checkedUID) {
			return true;
		}
		return false;

	}
}