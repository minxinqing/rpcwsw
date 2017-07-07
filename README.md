## rpcwsw
基于swoole扩展的rpc组件，能够轻松把restful接口转变成rpc服务。

## 依赖
swoole2.07 +  
laravel5.1 +

## 安装
1. composer.json添加源（还未发布packagist，需要手动添加源）
```php
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/minxinqing/rpcwsw.git"
    }
]
```

2. 安装 package
```php
composer require minxinqing/rpcwsw
```

## 使用

### 服务端
1. 服务端编写好restful接口，如已写好GET接口 /news/list

2. 增加config\rpcwsw.php配置文件
    ```php
    <?php
    return [
        'server' => [
            'host' => '0.0.0.0',
            'port' => 9527,
            'pid_file' => storage_path('app/swoole_pid'),
            'log_file' => storage_path('logs/swoole_log'),
            'daemonize' => 1,
            'worker_num' => 10,
            'task_worker_num' => 0,
        ]
    ];
    ```  
    以上配置只是范例，所有配置参数可上 [swoole官网查看](https://wiki.swoole.com/wiki/page/274.html) 

3. app\Console\Kernel.php注册命令
    ```
    protected $commands = [
        ...
        \Rpcwsw\Server::class,
    ];
    ```

4. server启动
    ```
    php artisan rpcwsw:server start
    ```

### 客户端
1. 增加config\rpcwsw.php配置文件
    ```
    <?php
    return [
        'instance' => [
            'serverA' => [
                'host' => '0.0.0.0',
                'port' => 9527,
            ]
        ],
    ];
    ```

2. 调用服务
    ```
    $v = \Rpcwsw\server('serverA')->api('news/list', [], 'get');
    ```
