# yii2-workerman

This project uses the [Yii2 framework](https://www.yiiframework.com) and [Linkerman](https://github.com/zhanguangcheng/linkerman) (based on [Workerman](https://www.workerman.net)) to build a project template.

The purpose is to run the Yii 2 framework in Workerman to implement the resident memory to improve the performance.

## Requirements

- PHP >= 8.1

## Installation

```bash
git clone https://github.com/zhanguangcheng/yii2-workerman.git
cd yii2-workerman
composer install --optimize-autoloader --classmap-authoritative
```

## Start the service

Add to php.ini file
```ini
disable_functions=set_time_limit,header,header_remove,headers_sent,headers_list,http_response_code,setcookie,setrawcookie,session_start,session_write_close,session_status,session_id,session_name,session_save_path,session_regenerate_id,session_unset,session_destroy,session_set_cookie_params,session_get_cookie_params,is_uploaded_file,move_uploaded_file
```

For Linux
```bash
php server/start.php start
```

For Windows
```bash
windows.bat
```

## nginx proxy config example
```nginx configuration
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
* Save Session to Redis
* Automatically reloads files after changes and when process memory usage is too large
* .env config file support
* Support for multiple processes start
* Support for middleware, Built-in Guard, Ratelimiter and StaticFile

## Security Vulnerabilities

If you discover a security vulnerability within yii2-workerman, Please submit an [issue](https://github.com/zhanguangcheng/yii2-workerman/issues) or send an e-mail to zhanguangcheng at 14712905@qq.com. All security vulnerabilities will be
promptly addressed.

## References

* [Yii2 Benchmarking Test](https://github.com/joanhey/FrameworkBenchmarks/tree/master/frameworks/PHP/yii2)

