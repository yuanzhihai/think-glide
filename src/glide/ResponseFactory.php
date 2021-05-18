<?php

declare(strict_types=1);

namespace think\glide;

use League\Flysystem\FilesystemInterface;
use League\Glide\Responses\ResponseFactoryInterface;
use think\Response;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create response
     * @param FilesystemInterface $cache
     * @param string $path
     * @return Response
     * @throws \League\Flysystem\FileNotFoundException
     * @author Byron Sampson <xiaobo.sun@qq.com>
     */
    public function create(FilesystemInterface $cache, $path)
    {
        $contentType   = $cache->getMimetype($path);
        $contentLength = $cache->getSize($path);

        return Response::create()->data(stream_get_contents($cache->readStream($path)))
            ->header(
                [
                    'Content-Type'   => $contentType,
                    'Content-Length' => $contentLength
                ]
            );
    }
}
