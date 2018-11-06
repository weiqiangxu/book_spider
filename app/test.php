<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Sunra\PhpSimple\HtmlDomParser;

/**
  * 测试 -爬虫脚本
  */
class test implements AppFactory {

	// 初始化网站爬虫状态记录表
    public function initable(){

    }
    
    // 获取书城书籍链接
    public function book_link(){

    }

    // 更新书城书籍章节|基本信息
    public function updateBook($book_list){

    }

	public function start()
	{
		$Save = new Save();
		$temp=array(
			'type' => '玄幻',
			'book_name' => '霸道总裁爱上我',
			'author_name' => '小花痴',
			'updatetime' => '2018-09-02 12:56',
			'instruct' => '有一个傻白甜遇到了一个腹黑女，后来被一个高富帅解救，全剧终！',
			'book_status' => '连载',
			'image_url' => 'https://www.biqugex.com/a.jpg',
			'website_id' => 9
		);

		$book_info_id = $Save->saveBookInfo($temp);
		var_dump($book_info_id);

	}

}