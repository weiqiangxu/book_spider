<?php

// 初始化
require_once('./lib/init.php');
// 顶点小说
require_once('./app/booktxt.php');

$booktxt = new booktxt();

if(isset($argv[1])){
	$last_number = $argv[1];
}else{
	$last_number = 'unlimit';
}

$booktxt->start($last_number);