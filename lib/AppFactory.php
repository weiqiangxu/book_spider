<?php

// 书城网站爬虫工厂

interface AppFactory{

	// 初始化网站爬虫状态记录表
    public function initable();
    
    // 获取书城书籍链接
    public function book_link();

    // 更新书城书籍章节|基本信息
    public function updateBook($book_list);

    // 当前书城爬虫启动
    public function start();
}