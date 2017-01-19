<?php
return [
    'server' => [
        'host' => '0.0.0.0',
        'port' => 9527,
        'pid_file' => storage_path('app/swoole_pid'),
        'log_file' => storage_path('logs/swoole_log'),
        'daemonize' => 1,
    ],
    'instance' => [
        'serviceA' => [
            'host' => '0.0.0.0',
            'port' => 9527,
        ],
        'serviceB' => [
            'host' => '0.0.0.0',
        ]
    ],
];