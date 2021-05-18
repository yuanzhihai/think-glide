<?php

declare(strict_types=1);

namespace think;

use League\Glide\Server;
use League\Glide\ServerFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use think\glide\ResponseFactory;
use think\glide\SignatureFactory;
use think\glide\UrlBuilderFactory;

class Glide
{
    /**
     * 当前应用对象
     * @var
     */
    protected $app;
    /**
     * @var array
     */
    protected $options;
    /**
     * @var array
     */
    protected $query;

    public function __construct(App $app, array $options = [])
    {
        $this->app = $app;
        $resolver  = new OptionsResolver();
        $resolver->setDefaults(
            [
                'source'      => $app->getRootPath() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload',
                'baseUrl'     => '/images',
                'cache'       => $app->getRuntimePath() . '/glide',
                'cacheTime'   => '+1 day',
                'signKey'     => '',
                'glide'       => [],
                'onException' => function (\Exception $exception, Request $request, Server $server) {
                    // 异常处理
                    if ($exception instanceof \League\Glide\Signatures\SignatureException) {
                        $response = Response::create('图片资源签名错误')->code(403);
                    } else {
                        $response = Response::create(sprintf('你访问的图片资源 "%s" 不存在', $request->pathinfo()))->code(404);
                    }

                    return $response;
                },
            ]
        );
        $resolver->setRequired('source');
        $this->options = $resolver->resolve($options);
        //如果启动安全校验，需要注入服务
        $urlBuilder = UrlBuilderFactory::create($this->options['baseUrl'], $this->options['signKey']);
        $this->app->bind('glide_builder', $urlBuilder);
    }

    /**
     * 把对象当成一个函数去执行
     * @param Request $request
     * @param $next
     * @return mixed
     * @author By yzh52521 <396751927@qq.com>
     */
    public function __invoke($request, $next)
    {
        $uri = urldecode($request->pathinfo());
        parse_str($request->query(), $this->query);
        unset($this->query['s']);
        if (!preg_match("#^{$this->options['baseUrl']}#", '/' . $uri)) {
            return $next($request);
        }
        $server = $this->createGlideServer();

        try {
            //检查安全签名
            $this->checkSignature($uri);
            $response = $this->handleRequest($server, $request);
        } catch (\Exception $exception) {
            $response = call_user_func($this->options['onException'], $exception, $request, $server);
        }

        return $response;
    }

    /**
     * @param Server $server
     * @param Request $request
     * @return Response
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \League\Glide\Filesystem\FileNotFoundException
     * @author By yzh52521 <396751927@qq.com>
     */
    protected function handleRequest(Server $server, Request $request)
    {
        //检查是否重新更新了
        $modifiedTime = null;
        if ($this->options['cacheTime']) {
            $modifiedTime = $server->getSource()
                ->getTimestamp($server->getSourcePath($request->pathinfo()));
            $response     = $this->applyModified($modifiedTime, $request);
            if (false !== $response) {
                return $response;
            }
        }
        //如果已经更新了重新从缓存拉取图像
        if (null === $server->getResponseFactory()) {
            $server->setResponseFactory(new ResponseFactory());
        }
        $response = $server->getImageResponse($request->pathinfo(), $this->query);

        return $this->applyCacheHeaders($response, $modifiedTime);
    }

    /**
     * 附加缓存标识
     * @param Response $response
     * @param $modifiedTime
     * @return Response
     * @author By yzh52521 <396751927@qq.com>
     */
    protected function applyCacheHeaders(Response $response, $modifiedTime)
    {
        $expire = $this->options['cacheTime'] ? strtotime($this->options['cacheTime']) : time();
        $maxAge = $expire - time();
        return $response
            ->header(
                [
                    'Cache-Control' => 'public,max-age=' . $maxAge,
                    'Date'          => gmdate('D, j M Y G:i:s \G\M\T', time()),
                    'Last-Modified' => gmdate('D, j M Y G:i:s \G\M\T', (int)$modifiedTime),
                    'Expires'       => gmdate('D, j M Y G:i:s \G\M\T', $expire)
                ]
            );
    }

    /**
     * @param int $modifiedTime
     * @param Request $request
     * @return false|Response
     * @author By yzh52521 <396751927@qq.com>
     */
    protected function applyModified($modifiedTime, Request $request)
    {
        //如果没有修改直接返回
        if ($this->isNotModified($request, $modifiedTime)) {
            $response = Response::create('')->code(304);
            return $this->applyCacheHeaders($response, $modifiedTime);
        }
        return false;
    }

    /**
     * @param Request $request
     * @param $modifiedTime
     * @return bool
     * @author By yzh52521 <396751927@qq.com>
     */
    protected function isNotModified(Request $request, $modifiedTime)
    {
        $modifiedSince = $request->header('If-Modified-Since');
        if (!$modifiedSince) {
            return false;
        }
        return strtotime($modifiedSince) === (int)$modifiedTime;
    }

    /**
     * @param string $uri
     * @throws \League\Glide\Signatures\SignatureException
     */
    protected function checkSignature($uri)
    {
        if (!$this->options['signKey']) {
            return;
        }
        SignatureFactory::create($this->options['signKey'])->validateRequest(
            $uri,
            $this->query
        );
    }

    /**
     * @return \League\Glide\Server
     * @author  By yzh52521 <396751927@qq.com>
     */
    protected function createGlideServer()
    {
        return ServerFactory::create(
            array_merge(
                [
                    'source'   => $this->options['source'],
                    'cache'    => $this->options['cache'],
                    'base_url' => $this->options['baseUrl'],
                ],
                $this->options['glide']
            )
        );
    }
}
