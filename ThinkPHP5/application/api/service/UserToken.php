<?php 

namespace app\api\service;
use think\Exception;
use app\lib\exception\WeChatException;
use app\api\model\User as UserModel;
use app\lib\enum\ScopeEnum;
class UserToken extends Token
{
	protected $code;
	protected $wxAppID;
	protected $wxAppSecret;
	protected $wxLoginUrl;
	function __construct($code){
		$this->code = $code;
		$this->wxAppID = config('wx.app_id');
		$this->wxAppSecret = config('wx.app_secret');
		$this->wxLoginUrl = sprintf(config('wx.login_url'),$this->wxAppID,$this->wxAppSecret,$this->code);


	}
	public function get(){
		$result = curl_get($this->wxLoginUrl);
		$wxResult = json_decode($result,true);
		// 请求失败
		if (empty($wxResult)) {
			throw new Exception("获取session_key及openID时异常，微信内部错误",1);
			
		}else{

			$loginFail = array_key_exists('errcode', $wxResult);
			if ($loginFail) {
				// 请求微信端报错
				$this->processLogininError($wxResult);
			}else{
				// 请求微信端正常 并返回token
				return $this->grantToken($wxResult);
			}
		}
	}
	private function grantToken($wxResult){
		//拿到openid
		//数据库里看一下，这个openid是不是已经存在
		//如果存在则不处理，如果不存在那么新增一条user记录
		//生成令牌，准备缓存数据，写入缓存
		//把令牌返回到客户端
		//key:令牌
		//value:wxResult,uid,scope
		$openid = $wxResult['openid'];
		$user = UserModel::getByOpenId($openid);
		if ($user) {
			$uid = $user->id;
		}else{
			$uid = $this->newUser($openid);
		}
		$cachedValue = $this->prepareCachedValue($wxResult,$uid);
		$token = $this->saveToCache($cachedValue);
		// 返回token
		return $token;
	}
	// 执行存入缓存
	private function saveToCache($cachedValue){
		// 获取token 在token.php里
		$key = self::generateToken();
		$value = json_encode($cachedValue);
		$expire_in = config('setting.token_expire_in');
		// 存入缓存       键    值    时间
		$request = cache($key,$value,$expire_in);
		if (!$request) {
			throw new TokenExpetion([
				'msg' => '服务器缓存异常',
				'errorCode' => 10005
				]);
		}
		// 返回token
		return $key;
	}
	// 组织要存的缓存数据
	private function prepareCachedValue($wxResult,$uid){
		// 微信返回的json
		$cachedValue = $wxResult;
		// user表里的id
		$cachedValue['uid'] = $uid;
		// 级别 访问接口的权限  scope = 16代表app用户的权限 
		$cachedValue['scope'] = ScopeEnum::User;

		// scope = 32代表cms(管理员)用户的权限 
		// $cachedValue['scope'] = 32;
		return $cachedValue;
	}
	private function newUser($openid){
		$user = UserModel::create([
			'openid' =>$openid
			]);
		return $user->id;
	}
	private function processLogininError($wxResult){
		throw new WeChatException([
			'msg' => $wxResult['errmsg'],
			'errorCode'=>$wxResult['errcode']
			]);
	}






}