<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Sunra\PhpSimple\HtmlDomParser;

/**
  * 顶点小说 -爬虫脚本
  */
class us implements AppFactory {

	// 初始化所有数据表
	public function initable()
	{
		// 列表
		if(!Capsule::schema()->hasTable('temp_us_book_list'))
		{
			Capsule::schema()->create('temp_us_book_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('page')->nullable()->comment('页码');
			});
			echo "table temp_us_book_list create".PHP_EOL;
		}

		if(!Capsule::schema()->hasTable('temp_us_book_link'))
		{
			Capsule::schema()->create('temp_us_book_link', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table temp_us_book_link create".PHP_EOL;
		}

		
		// 书籍章节列表
		if(!Capsule::schema()->hasTable('temp_us_menu_list'))
		{
			Capsule::schema()->create('temp_us_menu_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('book_info_id')->nullable();
			    $table->string('menu_name')->nullable();
			    $table->string('menu_id')->nullable();
			});
			echo "table temp_us_menu_list create".PHP_EOL;
		}
	}



	// 获取所有书籍链接
	public function book_link()
	{
		// 获取所有列表页
		$Download = new Download();
		@mkdir(APP_DOWN.'temp_us_book_list', 0777, true);
		$file = APP_DOWN.'temp_us_book_list/index.html';
		$Download->down('https://m.23us.la/class/0_1.html',$file);
		$html = mb_convert_encoding(file_get_contents($file),"UTF-8");// dom解析
		@unlink($file);
		if($dom = HtmlDomParser::str_get_html($html))
		{
			$max_list = $dom->find('#txtPage',0)->value;
			$max_list = str_replace('1/', '', $max_list);
			for ($i=1; $i < $max_list; $i++) { 
				$url = 'https://m.23us.la/class/0_'.$i.'.html';
				echo $url.PHP_EOL;
				$temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'page' => $i,
			    ];
			    $empty = Capsule::table('temp_us_book_list')->where('url',$url)->get()->isEmpty();
			    if($empty) Capsule::table('temp_us_book_list')->insert($temp);
			}
		}


		// 下载列表页
		$empty = Capsule::table('temp_us_book_list')->where('status','wait')->get()->isEmpty();
		@mkdir(APP_DOWN.'temp_us_book_list', 0777, true);
		while(!$empty) {
			$datas=Capsule::table('temp_us_book_list')->where('status','wait')->orderBy('id')->limit(60)->get();
			$temp = array();
			foreach ($datas as $k => $v)
			{
				$temp[] = array(
					'url' => $v->url,
					'file' => APP_DOWN.'temp_us_book_list/'.$v->id.'.html',
				);
			}
			@mkdir(APP_DOWN.'temp_us_book_list', 0777, true);
			$Download->pool($temp);

			// 解析列表页
			foreach ($datas as $k => $v)
			{
		    	$file = APP_DOWN.'temp_us_book_list/'.$v->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8");
					@unlink($file);
					// dom解析
		    		if($dom = HtmlDomParser::str_get_html($html))
					{	
						$prefix = 'https://m.23us.la';
						foreach($dom->find('.hot_sale') as $kk => $hot)
						{
							echo 'book_list '.$kk.PHP_EOL;
							$url = $prefix.$hot->find('a',0)->href;
							$temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    ];
						    $empty = Capsule::table('temp_us_book_link')->where('url',$url)->get()->isEmpty();
						    if($empty){
						    	Capsule::table('temp_us_book_link')->insert($temp);	
						    } else {
						    	Capsule::table('temp_us_book_link')->where('url',$url)->update(array('status'=>'wait'));
						    }
						}
			            Capsule::table('temp_us_book_list')->where('id', $v->id)->update(['status' =>'readed']);
			            echo 'temp_us_book_list '.$v->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		    $empty = Capsule::table('temp_us_book_list')->where('status','wait')->get()->isEmpty();
		}
	}


	// 循环遍历book_link表 - 更新|新增章节、书籍基本信息
	public function updateBook($book_link)
	{
		// 下载
		$Save = new Save();
		$Download = new Download();
		$Save = new Save();
		@mkdir(APP_DOWN.'temp_us_book_link', 0777, true);
		echo 'download book_link'.$book_link->id.'.html'.PHP_EOL;
		logdebug('analyse book_link'.$book_link->id.'.html','us');
		$file = APP_DOWN.'temp_us_book_link/'.$book_link->id.'.html';
		$Download->down($book_link->url,$file);
		$website_id = 4;

    	// 判定是否已经存在且合法
    	if (file_exists($file))
    	{
    		// 字符编码转换
			$html = mb_convert_encoding(file_get_contents($file),"UTF-8");
			@unlink($file);
			// dom解析
    		if($dom = HtmlDomParser::str_get_html($html))
			{
				$cover = $dom->find('.synopsisArea',0)->find('img',0)->src;
				$author_name = $dom->find('.synopsisArea_detail',0)->find('p',0)->innertext;

				foreach ($dom->find('.synopsisArea_detail',0)->find('p') as $z)
				{
					
					if(preg_match('/更新/', $z->innertext))
					{
						$uptime = str_replace('更新：', '', $z->innertext);
					}
					if(preg_match('/状态/', $z->innertext))
					{
						$book_status = str_replace('状态：', '', $z->innertext);
					}
				}
				$book_name = $dom->find('.title',0)->innertext;
				$instruct = $dom->find('.review',0)->innertext;
				$type = $dom->find('.sort',0)->innertext;
				
				$cpbookid = str_replace('https://m.23us.la/html/', '', $book_link->url);
				$cpbookid = explode('/', $cpbookid);
				$cpbookid = array_filter($cpbookid);
				$cpbookid = end($cpbookid);			    

				$temp=array(
					'type' => trim(str_replace('类别：', '', $type)),
					'book_name' => trim($book_name),
					'author_name' => str_replace('作者：','',trim($author_name)),
					'updatetime' =>trim($uptime),
					'instruct' => trim(strip_tags(str_replace('简介：', '', $instruct))),
					'book_status' => trim($book_status),
					'image_url' => $cover,
					'website_id' => $website_id,
					'tag' => trim(str_replace('类别：', '', $type)),
					'cpbookid' => $cpbookid,
				);

				$book_info_id = $Save->saveBookInfo($temp);

				// 获取所有的章节
				$file = APP_DOWN.'temp_us_book_link/'.$book_link->id.'_menu_list.html';
				$Download->down($book_link->url.'all.html',$file);

				// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = str_replace('""=style=""', '""', file_get_contents($file)) ;
					@unlink($file);
					if($dom = HtmlDomParser::str_get_html($html))
					{
						foreach ($dom->find('#chapterlist',0)->find('a') as $kk=> $a) {
							if($a->href == '#bottom'){continue;}
							if(!$a->href){continue;}
							$url = 'https://m.23us.la'.$a->href;
							$temp=array(
								'url'=> $url,
								'book_info_id'=>$book_info_id,
								'menu_name'=>$a->innertext,
								'menu_id'=>$kk,
								'status'=>'wait'		
							);
							$empty = Capsule::table('temp_us_menu_list')->where('url',$url)->get()->isEmpty();
					    	if($empty){
								echo $url.PHP_EOL;
					    		Capsule::table('temp_us_menu_list')->insert($temp);
								echo 'us temp_us_menu_list '.$kk.' insert success'.PHP_EOL;
					    	}else{
					    		echo $url.' has analysed!'.PHP_EOL;
					    	}

						}
					}
		    	}
		    	// 下载所有章节并入库
		    	$t = array('status'=>'wait','book_info_id'=>$book_info_id);
				$empty=Capsule::table('temp_us_menu_list')->where($t)->get()->isEmpty();

				while (!$empty) {
					
					$datas=Capsule::table('temp_us_menu_list')->where($t)->orderBy('id')->limit(50)->get();
					$temp = array();
					foreach ($datas as $key => $data) {
						$temp[] = array(
							'url' => $data->url,
							'file' => APP_DOWN.'temp_us_menu_list/'.$data->id.'.html',
						);
					}
					@mkdir(APP_DOWN.'temp_us_menu_list', 0777, true);
					$Download->pool($temp);

					// 解析
					foreach ($datas as $data) {
						$file = APP_DOWN.'temp_us_menu_list/'.$data->id.'.html';
						if (file_exists($file))
				    	{
				    		// 字符编码转换
							$html = mb_convert_encoding(file_get_contents($file),"UTF-8");
							@unlink($file);
							// dom解析
				    		if($dom = HtmlDomParser::str_get_html($html))
							{
								if($dom->find('#chaptercontent',0))
								{
									$menu_detail = $dom->find('#chaptercontent',0)->innertext;
									$menu_detail = preg_replace('/<p[\s\S]+<\/p>/iU', '', $menu_detail);
									$menu_detail = preg_replace('/<script[\s\S]+<\/script>/iU', '', $menu_detail);
									$menu_detail = html_entity_decode($menu_detail);
									$menu_detail = str_replace('</br>', '', $menu_detail);
									$menu_detail=str_replace('§§§§','§§',str_replace('<br/>','§§',$menu_detail));
									$menu_detail = filterEmoji($menu_detail);
									$menu_detail = removeEmoji($menu_detail);
									$temp=array(
										'book_id' => $data->book_info_id,
										'menu_content'=> trim($menu_detail),
										'menu_id' => $data->menu_id,
										'menu_name' => trim($data->menu_name),

									);
									// 保存章节信息
									$Save->saveBookMenu($temp);
								}
								echo 'us23 temp_us_menu_list/'.$data->id.'.html analyse!'.PHP_EOL;
								Capsule::table('temp_us_menu_list')->where('id',$data->id)->update(['status'=>'readed']);
							}
				    	}
					}
					$empty=Capsule::table('temp_us_menu_list')->where($t)->get()->isEmpty();
				}
	            echo 'temp_us_book_link '.$book_link->id.'.html'."  analyse successful!".PHP_EOL;
			}
    	}
	}


	public function start($last_number = 'unlimit')
	{
		$this->initable();

		// $this->book_link();
		
		if($last_number != 'unlimit') {
			$t = array(
				['status', '=', 'wait'],
				['id','like',"%{$last_number}"],
			);
    	} else {
	    	$t = array(
				['status', '=', 'wait'],
			);
    	}
		$empty = Capsule::table('temp_us_book_link')->where($t)->get()->isEmpty();
		while(!$empty) {
			$datas=Capsule::table('temp_us_book_link')->where($t)->limit(50)->get();
		    foreach ($datas as $data)
		    {
		    	$this->updateBook($data);
			    Capsule::table('temp_us_book_link')->where('id',$data->id)->update(array('status'=>'completed'));
		    }
		    $empty = Capsule::table('temp_us_book_link')->where($t)->get()->isEmpty();
		}

	}

}