<?php

declare(strict_types=1);

namespace think\glide;

use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use think\Response;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create response
     * @param FilesystemOperator $cache
     * @param string $path
     * @return Response
     */
    public function create(FilesystemOperator $cache, $path)
    {
        $contentType   = $cache->mimeType($path);
        $contentLength = $cache->fileSize($path);

        return Response::create()->data(stream_get_contents($cache->readStream($path)))
            ->header(
                [
                    'Content-Type'   => $contentType,
                    'Content-Length' => $contentLength
                ]
            );
    }
}
