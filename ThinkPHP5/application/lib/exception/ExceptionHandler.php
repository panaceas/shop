<?php 
namespace app\lib\exception;

use think\Exception;
use think\exception\Handle;
use think\Request;
use think\Log;
use think\Config;
class ExceptionHandler extends Handle{

	private $code;
	private $msg;
	private $errorCode;
	//需要返回客户端当前请求的URL路径
	
	public function render(\Exception $e){
		// 如果是继承的BaseException 证明是自定义的异常信息
		if($e instanceof BaseException){
			//如果是自定义的异常
			$this->code = $e->code;
			$this->msg = $e->msg;
			$this->errorCode = $e->errorCode;
		}else{
			// 如果de_bug开着 抛出自定义的错误
			if (config('app_debug')) {
				// 此处是调用父类的render方法抛出我们自定义的异常信息，并没有用我们自定义的render
				return parent::render($e);
			}else{
				$this->code = 500;
				$this->msg = '服务器内部错误';
				$this->errorCode = 999;
				$this->recodErrorLog($e);
			}
			
		}
		$request = Request::instance();
		$result = [
			'msg' => $this->msg,
			'errorCode' => $this->errorCode,
			'resquest_url' => $request->url()
		];
		return json($result,$this->code);
	}
	private function recodErrorLog(\Exception $e){
		Log::init([
			'type' => 'File',
			'path' => LOG_PATH,
			'level'=> ['error']
			]);
		Log::record($e->getMessage(),'error');
	}
}