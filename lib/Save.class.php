<?php
// 入库
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Save{

	/**
		* 保存书籍信息
		* @author xu
		* @copyright 2017-12-16
		* @param [arr] $data 书籍信息 [
		*		'book_id' => '图书ID'
		*		'website_id' => '合作方编号'
		*		'book_name' => '图书名'
		*		'author_name' => '作者名称'
		*		'book_status' => '书籍完结状态：0断更 1连载 2完结'
		*		'type' => '分类'
		*		'image_url' => '封面地址'
		*        'updatetime' => '最近更新时间'
		*        'instruct' => '简介'
		*        'tag' => '标签'
		*        'cpbookid' => '合作方书籍ID'
		*	]
		* @param [string] $file 文件保存地址
		* @return success:boolean[true]; erro:false
	*/
	public function saveBookInfo($data)
	{
		
		$ftype_id = $this->getTypeId($data['type']);
		// 封面转存
		$image_url = $this->saveBookPic($data['image_url']);
		if($data['book_status'] == '完结') {
			$book_status = 2;
			$finish_time = time();
		}else{
			$book_status = 1;
			$finish_time = 0;
		}

		$temp = array(
			'website_id' => $data['website_id'],
			'book_status' => $book_status,
			'ftype_id' => $ftype_id,
			'stype_id' => 0,
			'attribution' => 3,
			'image_url' => $image_url,
			'finish_time' => $finish_time,
			'instruct' => $data['instruct'],
			'tag' => $data['tag'],
		);

		// 检测是否已经在库中
		$map = array(
			['book_name',$data['book_name']],
			['author_name',$data['author_name']],
		);
		$book_info = Capsule::table('book_main')->where($map)->first();
    	if(empty($book_info))
    	{
    		// 添加书籍
    		$temp['uptime'] = time();
			$temp['book_name'] = $data['book_name'];
			$temp['author_name'] = $data['author_name'];
    		$book_id = Capsule::table('book_main')->insertGetId($temp);
    	}else{
    		// 更新书籍
    		Capsule::table('book_main')->where('book_id',$book_info->book_id)->update($temp);
    		$book_id = $book_info->book_id;
    	}

    	// 合作方书籍表
    	$spider_info = Capsule::table('spider_info')->where('bookid',$book_id)->first();
		if(empty($spider_info))
		{
			$temp = array(
				'cpid' => $data['website_id'],
				'cpbookid' => $data['cpbookid'],
				'bookid' => $book_id,
				'bookname' => $data['book_name'],
				'author' => $data['author_name'],
				'updatetime' => $data['updatetime'],
			);
			Capsule::table('spider_info')->insert($temp);
		}
		return $book_id;
	}
	


	/**
		* 新增|保存章节信息
		* @author xu
		* @copyright 2017-12-16
		* @param [arr] $data 章节内容 [
		*		'book_id' => '图书ID'
		*       'menu_id' => '章节ID'
		*		'menu_name' => '合作方编号'
		*		'menu_content' => '图书名'
		*	]
		* @param [string] $file 文件保存地址
		* @return success:boolean[true]; erro:false
	*/
	public function saveBookMenu($data)
	{

		// 检测书籍表是否最大章节
		$map = array(
			'book_id' => $data['book_id'],
		);
		$temp = Capsule::table('book_main')->where($map)->first();
		if(empty($temp)){
			return false;
		}

		if(intval($temp->max_menu_id)<intval($data['menu_id']))
		{
			$new = array(
				'menu_count' => $temp->menu_count +1,
				'max_menu_name' => $data['menu_name'],
				'max_menu_id' => $data['menu_id'],
				'max_menu_uptime' => time(),
			);
			Capsule::table('book_main')->where($map)->update($new);
		}

		// 检测章节列表
		$map = array(
			'book_id' => $data['book_id'],
			'menu_id' => $data['menu_id'],
		);
		$menu_info = Capsule::table('book_main_menu')->where($map)->first();
		if(empty($menu_info)) {
			$new = array(
				'book_id' => $data['book_id'],
				'menu_id' => $data['menu_id'],
				'menu_name' => $data['menu_name'],
				'menu_content' => $data['menu_content'],
				'check_status' => '1',
				'add_time' => time(),
				'upd_time' => time(),
				'word_count' => mb_strlen($data['menu_content']),
			);
			$id = Capsule::table('book_main_menu')->insertGetId($new);
    	} else {
    		$new = array(
				'menu_name' => $data['menu_name'],
				'menu_content' => $data['menu_content'],
				'check_status' => '1',
				'upd_time' => time(),
				'word_count' => mb_strlen($data['menu_content']),

			);
			$id = Capsule::table('book_main_menu')->where($map)->update($new);
    	}
    	return $id;
	}

	/**
		* 上传封面图片到文件服
		* @author xu
		* @copyright 2017-12-16
		* @param [string] $book_id 书籍ID 
		* @param [string] $pic_url 封面地址
		* @return 成功：pic_route图片的HTTP访问路径、失败：false
	*/
	public function saveBookPic($pic_url)
	{
		return $pic_url;
	}


	/**
		* 保存分类
		* @author xu
		* @copyright 2017-12-16
		* @param [string] $type 分类名 
		* @return 获取分类ID
	*/
	public function getTypeId($type)
	{
		$temp = Capsule::table('book_main_type')->where('typename',$type)->first();
		if(empty($temp)){
			$temp = array('typename'=>$type,'channelid'=>3,'parent_name'=>'');
			$id = Capsule::table('book_main_type')->insertGetId($temp);
		}else{
			$id = $temp->id;
		}
		return $id;
	}
}