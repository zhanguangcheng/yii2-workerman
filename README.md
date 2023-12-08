# yii2-workerman

This project uses the [Yii2 framework](https://www.yiiframework.com) and [Linkerman](https://github.com/zhanguangcheng/linkerman) (based on [Workerman](https://www.workerman.net)) to build a project template.

The purpose is to run the Yii 2 framework in Workerman to implement the resident memory to improve the performance.

## Requirements

- PHP >= 8.0

## Installation

```bash
git clone https://github.com/zhanguangcheng/yii2-workerman.git
cd yii2-workerman
composer install --optimize-autoloader --classmap-authoritative

#Replace exit() to prevent worker from exiting abnormally
sed -i 's|exit($status)|exit_exception($status)|g' vendor/yiisoft/yii2/base/Application.php
sed -i 's|exit(1);|exit_exception(1);|g' vendor/yiisoft/yii2/base/ErrorHandler.php
```

## Start the service

```bash
php server.php start
```

## nginx proxy config example
```
http {
    #...
    
    upstream backend {
        server 127.0.0.1:8080;
		keepalive 10240;
    }
    server {
        listen       80;
        server_name  localhost;
        location / {
            try_files $uri @php;
        }
        location @php {
            proxy_pass http://backend;
            proxy_http_version 1.1;
			proxy_set_header Connection "";
            proxy_set_header Host $host;
            proxy_set_header HTTPS $https;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        }
    }
}
```

## Features

* Run the Yii2 framework in Workerman
* Error handling
* Reuse database Connections
* Reconnect after database disconnection
* Reuse Redis Connections

## Security Vulnerabilities

If you discover a security vulnerability within yii2-workerman, Please submit an [issue](https://github.com/zhanguangcheng/yii2-workerman/issues) or send an e-mail to zhanguangcheng at 14712905@qq.com. All security vulnerabilities will be
promptly addressed.

## References

* [Yii2 Benchmarking Test](https://github.com/joanhey/FrameworkBenchmarks/tree/master/frameworks/PHP/yii2)

