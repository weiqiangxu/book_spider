<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Sunra\PhpSimple\HtmlDomParser;

/**
  * 言情小说园 -爬虫脚本
  */
class yqxsy implements AppFactory {

	// 检测状态记录表
	public function initable()
	{
		// 书籍列表-更新时候检测是否已经查过[依据路由地址]
		if(!Capsule::schema()->hasTable('temp_yqxsy_book_list'))
		{
			Capsule::schema()->create('temp_yqxsy_book_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable()->index();
			    $table->string('status')->nullable();
			});
			echo "table temp_yqxsy_book_list create".PHP_EOL;
		}

		// 书籍章节列表-更新时候检测是否已经查过[依据路由地址]
		if(!Capsule::schema()->hasTable('temp_yqxsy_menu_list'))
		{
			Capsule::schema()->create('temp_yqxsy_menu_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('book_info_id')->nullable();
			    $table->string('menu_name')->nullable();
			    $table->string('menu_id')->nullable();
			});
			echo "table temp_yqxsy_menu_list create".PHP_EOL;
		}
	}


	// 获取所有书籍列表
	public function book_link()
	{
		$Download = new Download();
		@mkdir(APP_DOWN.'temp_yqxsy_book_list', 0777, true);
		$file = APP_DOWN.'temp_yqxsy_book_list/index.html';
		$Download->down('https://www.yqxsy.com/paihangbang/',$file);
		$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");// dom解析
		
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('.box') as $box)
			{
				foreach($box->find('li') as $k => $li)
				{
					if($k==0){
						continue;
					}

					$url = $li->find('a',0)->href;
					if(!strpos($url, 'yqxsy.com')){
						continue;
					}
					echo $url.PHP_EOL;
					logdebug($url,'yqxsy');
					$temp = [
				    	'url' => $url,
				    	'status'=>'wait',
				    ];
				    $empty = Capsule::table('temp_yqxsy_book_list')->where('url',$url)->get()->isEmpty();
			    	if($empty) Capsule::table('temp_yqxsy_book_list')->insert($temp);
				}
			}
		}


	}


	// 循环遍历book_link表 - 更新|新增章节、书籍基本信息
	public function updateBook($book_link)
	{
		// 下载
		$Download = new Download();
		$Save = new Save();
		@mkdir(APP_DOWN.'temp_yqxsy_book_link', 0777, true);
		echo 'download temp_yqxsy_book_link'.$book_link->id.'.html'.PHP_EOL;
		logdebug('download temp_yqxsy_book_link'.$book_link->id.'.html','yqxsy');
		$file = APP_DOWN.'temp_yqxsy_book_link/'.$book_link->id.'.html';
		$Download->down($book_link->url,$file);
		$website_id = 7;

    	// 解析书籍信息
    	if (file_exists($file))
    	{
			$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
	    	$html=preg_replace('/id="list">[\S\s]+正文[\s]*<\/dt>/iU', 'id="list">', $html);
			// dom解析
    		if($dom = HtmlDomParser::str_get_html($html))
			{
				$type=$dom->find('#bdshare',0)->next_sibling()->next_sibling()->innertext;
				$book_name=$dom->find('#maininfo',0)->find('h1',0)->innertext;
				$author_name=$dom->find('#maininfo',0)->find('h1',0)->next_sibling()->innertext;
				$author_name=str_replace('作&nbsp;&nbsp;&nbsp;&nbsp;者：', '', $author_name);
				$uptime=$dom->find('#maininfo',0)->find('#info',0)->last_child()->prev_sibling()->innertext;
				$uptime=str_replace('最后更新：', '', $uptime);
				$instruct=$dom->find('#maininfo',0)->find('#intro',0)->find('p',0)->innertext;
				$instruct = trim(html_entity_decode($instruct));
				$cover=$dom->find("#fmimg",0)->find('script',0)->src;
				preg_match('/_(\d+)\//', $book_link->url,$cpbookid);
				$cpbookid = $cpbookid[1];


				$temp=array(
					'type' => trim($type),
					'book_name' => trim(html_entity_decode($book_name)),
					'author_name' => trim(html_entity_decode($author_name)),
					'updatetime' => trim($uptime),
					'instruct' => trim($instruct),
					'book_status' => '连载',
					'image_url' => 'https://www.yqxsy.com:443/'.trim($cover),
					'website_id' => $website_id,
					'tag' => trim($type),
					'cpbookid' => $cpbookid,
				);
				$book_info_id = $Save->saveBookInfo($temp);

				foreach($dom->find('.box_con',1)->find('a') as $kk=> $a)
				{
					$url = 'https://www.yqxsy.com'.$a->href;
					$temp=array(
						'url'=>'https://www.yqxsy.com'.$a->href,
						'book_info_id'=>$book_info_id,
						'menu_name'=>$a->innertext,
						'menu_id'=>$kk+1,
						'status'=>'wait'		
					);
					$empty = Capsule::table('temp_yqxsy_menu_list')->where('url',$url)->get()->isEmpty();
			    	if($empty) Capsule::table('temp_yqxsy_menu_list')->insert($temp);
				}

	            // 下载章节内容
	            $t = array('status'=>'wait','book_info_id'=>$book_info_id);
				$empty=Capsule::table('temp_yqxsy_menu_list')->where($t)->get()->isEmpty();
				while (!$empty) {
					$datas=Capsule::table('temp_yqxsy_menu_list')->where($t)->orderBy('id')->limit(50)->get();
					$temp = array();
					foreach ($datas as $key => $data) {
						$temp[] = array(
							'url' => $data->url,
							'file' => APP_DOWN.'temp_yqxsy_menu_list/'.$data->id.'.html',
						);
					}
					@mkdir(APP_DOWN.'temp_yqxsy_menu_list', 0777, true);
					$Download->pool($temp);

					// 解析章节内容
					foreach ($datas as $data) {
						$file = APP_DOWN.'temp_yqxsy_menu_list/'.$data->id.'.html';
						if (file_exists($file))
				    	{
				    		// 字符编码转换
							$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
							// dom解析
				    		if($dom = HtmlDomParser::str_get_html($html))
							{
								$content = $dom->find("#content",0)->innertext;
								$content = html_entity_decode($content);
								$content=str_replace('§§§§','§§',str_replace('<br />','§§',$content));
								
								$temp=array(
									'book_id' => $book_info_id,
									'menu_content'=> trim($content),
									'menu_id' => trim($data->menu_id),
									'menu_name' => trim($data->menu_name),
								);
								// 保存章节信息
								$Save->saveBookMenu($temp);
							}
							echo 'temp_yqxsy_menu_list/'.$data->id.'.html analyse!'.PHP_EOL;
							logdebug('temp_yqxsy_menu_list/'.$data->id.'.html analyse!','yqxsy');
							Capsule::table('temp_yqxsy_menu_list')->where('id', $data->id)->update(['status' =>'readed']);
						}
					}
					$empty=Capsule::table('temp_yqxsy_menu_list')->where($t)->get()->isEmpty();
				}
			}
    	}
	}


	public function start()
	{
		$this->initable();

		$this->book_link();
		
		$t = array(
			['status', '=', 'wait'],
		);
		$empty = Capsule::table('temp_yqxsy_book_list')->where($t)->get()->isEmpty();
		while(!$empty) {
			$datas=Capsule::table('temp_yqxsy_book_list')->where($t)->orderBy('id')->limit(10)->get();
		    foreach ($datas as $data)
		    {
		    	$this->updateBook($data);
		    	Capsule::table('temp_yqxsy_book_list')->where('id',$data->id)->update(array('status'=>'completed'));
		    }
		    $empty = Capsule::table('temp_yqxsy_book_list')->where($t)->get()->isEmpty();
		}

	}

}