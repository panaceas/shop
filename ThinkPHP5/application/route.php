<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


use think\Route;
// banner接口
Route::get('api/:version/banner/:id','api/:version.Banner/getBanner');

// 获取专题接口
Route::get('api/:version/theme','api/:version.Theme/getSimpleList');
// 某个专题包含的商品接口 
Route::get('api/:version/theme/:id','api/:version.Theme/getComplexOne');

// 分类下的商品接口
Route::get('api/:version/product/by_category','api/:version.Product/getAllInCategory');
// 商品详情接口
Route::get('api/:version/product/:id','api/:version.Product/getOne',[],['id'=>'\d+']);
// 查询商品接口
Route::get('api/:version/product/recent','api/:version.Product/getRecent');

// Route::group('api/:version/product',function(){
// 	// 分类下的商品接口
// 	Route::get('/by_category','api/:version.Product/getAllInCategory');
// 	// 商品详情接口
// 	Route::get('/:id','api/:version.Product/getOne',[],['id'=>'\d+']);
// 	// 查询商品接口
// 	Route::get('/recent','api/:version.Product/getRecent');
// });
// 分类接口
Route::get('api/:version/category/all','api/:version.Category/getAllCategories');

// 获取Token
Route::post('api/:version/token/user','api/:version.Token/getToken');

// 更新或创建收货地址
Route::post('api/:version/address','api/:version.Address/createOrUpdateAddress');
// 
Route::post('api/:version/order','api/:version.Order/placeOrder');
Route::get('api/:version/order/by_user','api/:version.Order/getSummaryByUser');
Route::get('api/:version/order/:id','api/:version.Order/getDetail',[],['id'=>'\d+']);

Route::post('api/:version/pay/pre_order','api/:version.Pay/getPreOrder');
// 微信回调
Route::post('api/:version/pay/notify','api/:version.Pay/receiveNotify');