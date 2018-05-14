<?php 

namespace app\api\validate;
use think\Request;
use think\Validate;
use app\lib\exception\ParameterException;
class BaseValidate extends Validate
{
	
	public function goCheck(){
		//http传入的参数
		//对这些参数做校验
		$resquest = Request::instance();
		$params = $resquest->param();
		$result = $this->check($params);
		if (!$result) {

			$e = new ParameterException([
					// 传入要修改的参数，用BaseValidate的构造函数更改
					'msg' => $this->error,
					// 'code' => 400,
					// 'errorCode' => 10002
				]);
			throw $e;
		}else{
			return true;
		}
	}
	protected function isPositiveInteger($value,$rule = '',$data = '',$field = ''){
		if(is_numeric($value) && is_int($value + 0) && ($value + 0)> 0){
			return true;
		}else{
			return false;
			// return $field.'必须是正整数';
		}
	}
    //没有使用TP的正则验证，集中在一处方便以后修改
    //不推荐使用正则，因为复用性太差
    //手机号的验证规则
    protected function isMobile($value)
    {
        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
	protected function isNotEmpty($value,$rule = '',$data = '',$field = ''){
		if(!empty($value)){
			return true;
		}else{
			return false;
			
		}
	}
	// 获取指定的数据变量
	public function getDataByRule($arrays){
		if (array_key_exists('user_id',$arrays) | array_key_exists('uid',$arrays)){
			//不让包含user_id或者uid，防止恶意覆盖user_id外键
			throw new ParameterException([
				'msg' => '参数中包含有非法的参数名user_id或者uid'
				]);
		}
		$newArray = [];
		// 只取验证过的数据 $this->rule是进行验证的数组 拿到他的key去传过来的数组$arrays 去找验证过的数据  这样保证取到的数据都是验证过的
		foreach ($this->rule as $key => $value) {
			$newArray[$key] =$arrays[$key];
		}
		return $newArray;
	}
}