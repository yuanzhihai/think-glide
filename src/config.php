<?php
use think\Request;

return [
    // 本地图片文件夹的位置
    'source' => app()->getRootPath() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload',
    // 路由前缀，匹配到该前缀时中间件开始执行
    'baseUrl' => '/images',
    // 缓存文件位置
    'cache' => app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'glide',
    // 缓存时间，示例 +2 days, 缓存期间多次请求会自动响应 304
    'cacheTime' => '+1 day',
    // 安全签名
    'signKey' => false,
    'glide' => [],
    // 异常处理handler
    'onException' => function(\Exception $exception, Request $request, $server){
        throw $exception;
    },
];
