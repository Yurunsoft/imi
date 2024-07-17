<?php

declare(strict_types=1);

$rootPath = \dirname(__DIR__) . '/';

return [
    'hotUpdate'               => [
        'status'          => false, // 关闭热更新去除注释，不设置即为开启，建议生产环境关闭

        // --- 文件修改时间监控 ---
        // 'monitorClass'    =>    \Imi\HotUpdate\Monitor\FileMTime::class,
        'timespan'        => 1, // 检测时间间隔，单位：秒

        // --- Inotify 扩展监控 ---
        // 'monitorClass'    =>    \Imi\HotUpdate\Monitor\Inotify::class,
        // 'timespan'    =>    1, // 检测时间间隔，单位：秒，使用扩展建议设为0性能更佳

        // 'includePaths'    =>    [], // 要包含的路径数组
        'excludePaths'    => [
            $rootPath . '.git',
            $rootPath . 'bin',
            $rootPath . 'logs',
        ], // 要排除的路径数组，支持通配符*
    ],
    // 启用超全局变量
    'SuperGlobals'            => [
        'enable'    => true,
    ],
    'AutoRunProcessManager'   => [
        'processes' => [
            'CronProcess',
        ],
    ],
    'CronManager'             => [
        'tasks' => [
        ],
    ],
    'HttpRoute' => [
        'autoEndSlash' => true,
    ],
];
