# taking_e
## 版本
version 1.0
一款使用邮箱注册聊天的项目
## 开发环境
php7.4 linux mysql swoole4.8 redis
### 数据库
data.sql
## 技术框架
thinkphp6 jq
## 运行
### 运行目录
前端运行目录:web_html
后端运行目录:index\public
swoole运行文件:index\server.php
### 运行流程
1.先把前端和后端目录挂载在NGINX上
2.修改配置文件,web_html\public\header.php,里面存放后端连接域名和swoole连接端口号,swoole服务端端口号配置在index\server.php中,
index\.env里面存放数据库,发送邮箱(qq邮箱),redis账号配置
3.php server.php
