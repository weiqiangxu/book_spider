<?php

// 初始化
require_once('./lib/init.php');
// 笔趣阁
require_once('./app/biqugex.php');

$biqugex = new biqugex();

$biqugex->start();