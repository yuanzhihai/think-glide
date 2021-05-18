<?php

declare(strict_types=1);

namespace think\glide\middleware;

use think\App;
use think\facade\Config;
use think\facade\Request;
use think\Glide as GlideFactory;

class Glide
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 图片缩放中间件
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @author Byron Sampson <xiaobo.sun@qq.com>
     */
    public function handle($request, \Closure $next)
    {
        $config     = Config::get('glide', []);
        $middleware = new GlideFactory($this->app, $config);

        return $middleware($request, $next);
    }
}
