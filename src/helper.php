<?php

declare(strict_types=1);

\think\Console::starting(
    function (\think\Console $console) {
        $console->addCommands(
            [
                'glide:config' => '\\think\\glide\\command\\SendConfig'
            ]
        );
    }
);

if (!function_exists('glide_url')) {
    /**
     * 快速生成链接
     * @param $path
     * @param array $param
     * @return mixed
     */
    function glide_url($path, $param = [])
    {
        return app('glide_builder')->getUrl($path, $param);
    }
}
