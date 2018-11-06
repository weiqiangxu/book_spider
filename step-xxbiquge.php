<?php

// 初始化
require_once('./lib/init.php');
// 新笔趣阁
require_once('./app/xxbiquge.php');

$xxbiquge = new xxbiquge();

$xxbiquge->start();