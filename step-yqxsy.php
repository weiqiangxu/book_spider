<?php

// 初始化
require_once('./lib/init.php');
// 言情小说园
require_once('./app/yqxsy.php');

$yqxsy = new yqxsy();

$yqxsy->start();