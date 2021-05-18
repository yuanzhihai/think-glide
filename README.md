# ThinkPHP6+ 图片动态裁剪缩放库

[Glide](https://github.com/thephpleague/glide) 是一个可以帮助你根据指定参数动态的生成图片内容给浏览器的图片操作库，从而实现
图片动态裁剪，打水印等，本库对 Glide 进行了一些友好的包装与扩展，屏蔽了原生库的一些底层抽象从而使得 ThinkPHP 用户可以在 ThinkPHP6 项目中
更好的添加图片的动态裁剪功能。

## Installation

执行下面命令安装:

```bash
$ composer require yzh52521/think-glide
```

## Usage

### Quick start

- ThinkPHP6 及以上版本使用 middleware 注册：

  打开 `application/middleware.php` 文件（如果不存在创建即可），注册 middleware：

    ```php
    return [
        //...
    
        \think\glide\middleware\Glide::class
    ];
    ```

### 生成配置

系统安装后会自动在 config 目录中生成 glide.php 的配置文件，
如果系统未生成可在命令行执行

```php
php think glide:config 
```

快速生成配置文件

### 参数说明

| 参数名 | 类型 | 说明 | 是否必选 |
| --- | --- | --- | --- |
| source | string | 本地文件夹位置 | 是 |
| cache| string | 缓存文件位置，默认在 `runtime/glide` 下面| 否 |
| cacheTime| string | 缓存时间，示例 `+2 days`, 缓存期间多次请求会自动响应 304| 否 |
| signKey | string | 安全签名 | 否 | 
| onException | callable | 异常处理handler | 否 | 
| baseUrl | string | 路由前缀，匹配到该前缀时中间件开始执行，默认是 `/images` | 否 | 

`source` 是你本地图片文件夹的位置，假设该目录下有图片 `user.jpg`, 打开浏览器访问下面链接：

```
http://youdomain.com/images/user.jpg?w=100&h=100
```
即可得到缩小后的图片。

### 安全签名

不开启安全签名的情况下用户可以调整query里面的参数自行对图片进行裁剪，如果你不打算这么做的话，你可以通过
`signKey` 进行校验，

这种情况下用户自行调整参数将会无效；生成安全的URL:

```php
echo glide_url('user.jpg', ['w' => 100, 'h' => 100]);
或
echo app('glide_builder')->getUrl('user.jpg', ['w' => 100, 'h' => 100]);

//你会得到如下链接：/images/user.jpg?w=100&h=100&sign=af3dc18fc6bfb2afb521e587c348b904
```

### 异常处理

如果用户访问了一张不存在的图片或者没有进行安全校验，系统会抛出异常，你可以通过修改配置文件中 `onException` 进行替换默认行为：

```php
return [
    //...

    'onException' => function(\Exception $exception, $request, $server){
    
        if ($exception instanceof \League\Glide\Signatures\SignatureException) {
            $response = response('签名错误', 403);
        } else {
            $response = response(sprintf('你访问的资源 "%s" 不存在', $request->path()), 404);
        }
        
        return $response;
    }
])
```

#### nginx 配置
```
location ~* /images/(.*)$ {
  try_files $uri $uri/ /index.php/$request_uri;
}
```

注意该闭包必须返回一个 `think\Response` 实例；

### Quick reference

不止支持裁剪，glide还支持其它操作，只要传递对应参数即可，参考这里查看支持的参数：

[http://glide.thephpleague.com/1.0/api/quick-reference/](http://glide.thephpleague.com/1.0/api/quick-reference/)

## License

See [MIT](https://opensource.org/licenses/MIT).
