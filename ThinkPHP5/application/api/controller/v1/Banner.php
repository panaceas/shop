<?php
namespace app\api\controller\v1;
use app\api\validate\IDMustBePostiveInt;
use app\api\model\Banner as BannerModel;
use think\Exception;
use app\lib\exception\BannerMissException;
class Banner
{   
    /**
     * 获取指定id的banner信息
     *  
     */
    public function getBanner($id)
    {   
        //AOP面向切面编程
        (new IDMustBePostiveInt())->batch()->goCheck();
            //模型就是对表的操作（解释不完全正确）
            // 下面是模型 对应的Model的Bannerclass 对应Banner表
            // get()是模型的一个方法
            // 推荐使用这样的静态调用
            // $banner = BannerModel::get($id);
          
             // 先实例化在调用模型
            // 不推荐先实例化在调用
            // $banner = new BannerModel();
            // $banner = $banner->get($id);
            
        $banner = BannerModel::getBannerById($id);
        // $banner->hidden('delete_time'); //隐藏字段
        // $banner->visible('id'); //显示字段
        if (!$banner) {
            throw new BannerMissException();
         }
        return $banner;
    }
}
