<?php 
namespace app\api\validate;
use app\lib\exception\ParameterException;
class OrderPlace extends BaseValidate
{	

	protected $rule = [
		'products' => 'checkProducts'
	];
	// 参数数组内子项的验证方法
	protected $singlerule = [
		'product_id' => 'require|isPositiveInteger',
		'count' =>'require|isPositiveInteger'
	];

	protected function checkProducts($values){
		if (empty($values)) {
			throw new ParameterException([
					'msg' => '商品列表不能为空' 
			]);
		}

		if (!is_array($values)) {
			throw new ParameterException([
					'msg' => '商品参数不正确'
			]);
		}
		// 遍历验证子项
		foreach ($values as $value) {
			$this->checkProduct($value);
		}
		return true;
	}
	protected function checkProduct($value){
		// 新new了方法 在调用 给了新的验证规则 去验证子项
		$validate = new BaseValidate($this->singlerule);
		$result = $validate->check($value);
		if (!$result) {
			throw new ParameterException([
				'msg' => '商品列表参数错误'
			]);
		}
	}
}