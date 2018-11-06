<?php

// 设置脚本超时
set_time_limit(3600*2);
// 内存限制
ini_set('memory_limit', '2014M');
// 默认时区
date_default_timezone_set('PRC');
// 声明编码
@header('Content-type:text/html;charset=utf-8');

// 类文件资源目录
defined('APP_PATH') or define('APP_PATH', dirname(__DIR__));
// 下载目录
defined('APP_DOWN') or define('APP_DOWN', dirname(__DIR__).'/cache/');

defined('MAX_FILE_SIZE') or define('MAX_FILE_SIZE', 60000000);

// 初始化
require_once(APP_PATH.'/lib/vendor/autoload.php');
// 链接数据库
require_once(APP_PATH.'/lib/config.php');
// 下载类
require_once(APP_PATH.'/lib/Download.class.php');
// 入库类
require_once(APP_PATH.'/lib/Save.class.php');
// 公共方法
require_once(APP_PATH.'/lib/function.php');
// 书城爬虫抽象工厂
require_once(APP_PATH.'/lib/AppFactory.php');

// 数据库操作对象
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
// 创建链接
$capsule->addConnection($database);

// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();

// 客户端关闭脚本终止
ignore_user_abort(true);