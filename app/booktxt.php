<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Sunra\PhpSimple\HtmlDomParser;

/**
  * 顶点小说 -爬虫脚本
  */
class booktxt implements AppFactory {

	// 检测状态记录表
	public function initable()
	{
		// 书籍列表-更新时候检测是否已经查过[依据路由地址]
		if(!Capsule::schema()->hasTable('temp_booktxt_book_list'))
		{
			Capsule::schema()->create('temp_booktxt_book_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable()->index();
			    $table->string('status')->nullable();
			});
			echo "table temp_booktxt_book_list create".PHP_EOL;
		}

		// 书籍
		if(!Capsule::schema()->hasTable('temp_booktxt_book_link'))
		{
			Capsule::schema()->create('temp_booktxt_book_link', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable()->index();
			    $table->string('status')->nullable();
			});
			echo "table temp_booktxt_book_link create".PHP_EOL;
		}

		// 章节列表页
		if(!Capsule::schema()->hasTable('temp_booktxt_menu_page'))
		{
			Capsule::schema()->create('temp_booktxt_menu_page', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->text('url')->nullable();
			    $table->string('book_info_id')->nullable()->comment('书籍简介ID');
			    $table->string('des')->nullable()->comment('说明');
			    $table->string('status')->nullable();
			});
			echo "table temp_booktxt_menu_page create".PHP_EOL;
		}


		// 书籍章节列表-更新时候检测是否已经查过[依据路由地址]
		if(!Capsule::schema()->hasTable('temp_booktxt_menu_list'))
		{
			Capsule::schema()->create('temp_booktxt_menu_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('book_info_id')->nullable();
			    $table->string('menu_name')->nullable();
			    $table->string('menu_id')->nullable();
			});
			echo "table temp_booktxt_menu_list create".PHP_EOL;
		}
	}


	// 检测是否有新书籍
	public function book_link()
	{
		// 获取所有的列表页
		$Download = new Download();
		@mkdir(APP_DOWN.'temp_booktxt_book_list', 0777, true);
		$file = APP_DOWN.'temp_booktxt_book_list/list.html';
		$Download->down('https://m.booktxt.net/wapsort/0_1.html',$file);
		$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");// dom解析
		if($dom = HtmlDomParser::str_get_html($html))
		{
			$page = 0;
			foreach($dom->find('.middle',0)->find('option') as $k => $option)
			{
				$page++;
			}
			for ($i=1; $i < $page; $i++) { 
				echo $i.PHP_EOL;
				$url = 'https://m.booktxt.net/wapsort/0_'.$i.'.html';
				logdebug($url,'booktxt');
				$temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    ];
			    $res = Capsule::table('temp_booktxt_book_list')->where('url',$url)->first();
		    	if(empty($res)){
		    		Capsule::table('temp_booktxt_book_list')->insert($temp);
		    	}else{
		    	Capsule::table('temp_booktxt_book_list')->where('id',$res->id)->update(array('status'=>'wait'));
		    	}
			}
		}

		// 下载列表获取书籍链接
        $t = array('status'=>'wait');
		$empty=Capsule::table('temp_booktxt_book_list')->where($t)->get()->isEmpty();

		while (!$empty) {
			
			$datas=Capsule::table('temp_booktxt_book_list')->where($t)->orderBy('id')->limit(50)->get();
			$temp = array();
			foreach ($datas as $key => $data) {
				$temp[] = array(
					'url' => $data->url,
					'file' => APP_DOWN.'temp_booktxt_book_list/'.$data->id.'.html',
				);
			}
			@mkdir(APP_DOWN.'temp_booktxt_book_list', 0777, true);
			$Download->pool($temp);

			// 解析获取书籍链接
			foreach ($datas as $data) {
				$file = APP_DOWN.'temp_booktxt_book_list/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
					// dom解析
		    		if($dom = HtmlDomParser::str_get_html($html))
					{	
						$prefix = 'https://m.booktxt.net';
						foreach($dom->find('.hot_sale') as $hot)
						{
							$url = $prefix.$hot->find('a',0)->href;
							$temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    ];
						    $res = Capsule::table('temp_booktxt_book_link')->where('url',$url)->first();
						    if(empty($res)) {
						    	Capsule::table('temp_booktxt_book_link')->insert($temp);
						    }else{
						    	Capsule::table('temp_booktxt_book_link')->where('id',$res->id)->update(array('status'=>'wait'));
						    }
						}
			            Capsule::table('temp_booktxt_book_list')->where('id', $data->id)->update(['status' =>'readed']);
			            echo 'temp_booktxt_book_list '.$data->id.'.html'."  analyse successful!".PHP_EOL;
			            logdebug('temp_booktxt_book_list '.$data->id.'.html'."  analyse successful!",'booktxt');
					}
		    	}
			}

			$empty=Capsule::table('temp_booktxt_book_list')->where($t)->get()->isEmpty();
		}
	}


	// 循环遍历book_link表 - 更新|新增章节、书籍基本信息
	public function updateBook($book_link)
	{
		// 下载
		$Download = new Download();
		$Save = new Save();
		@mkdir(APP_DOWN.'temp_booktxt_book_link', 0777, true);
		echo 'download temp_booktxt_book_link'.$book_link->id.'.html'.PHP_EOL;
		logdebug('download temp_booktxt_book_link'.$book_link->id.'.html','booktxt');
		$file = APP_DOWN.'temp_booktxt_book_link/'.$book_link->id.'.html';
		$Download->down($book_link->url,$file);
		$website_id = 6;

    	// 解析书籍信息
    	if (file_exists($file))
    	{
    		// 字符编码转换
			$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
			// dom解析
    		if($dom = HtmlDomParser::str_get_html($html))
			{
				if($dom->find('.synopsisArea',0))
				{
					foreach ($dom->find('.synopsisArea_detail',0)->find('p') as $p)
					{
						if(preg_match('/作者/', $p->innertext)){
							$author_name = str_replace('作者：','', $p->innertext);
						}
						if(preg_match('/类别/', $p->innertext)){
							$type = str_replace('类别：','', $p->innertext);
						}
						if(preg_match('/状态/', $p->innertext)){
							$status = str_replace('状态：','', $p->innertext);
						}
						if(preg_match('/更新/', $p->innertext)){
							$uptime = str_replace('更新：','', $p->innertext);
						}
					}

					$cover = $dom->find('.synopsisArea_detail',0)->find('img',0)->src;
					$book_name = $dom->find('.title',0)->innertext;
					$instruct = $dom->find('.review',0)->innertext;
					preg_match('/\d+/',$book_link->url,$t);
					$cpbookid = current($t);
				    $temp=array(
						'type' => trim($type),
						'book_name' => trim($book_name),
						'author_name' => trim($author_name),
						'updatetime' => trim($uptime),
						'instruct' => trim($instruct),
						'book_status' => trim($status),
						'image_url' => trim($cover),
						'website_id' => $website_id,
						'tag' => str_replace('分类：', '', $type),
						'cpbookid' => $cpbookid,
					);
					$book_info_id = $Save->saveBookInfo($temp);
				}
				

				// 章节列表页
				foreach($dom->find('.middle',0)->find('option') as $opt)
				{
					$des = $opt->innertext;
					$link = 'https://m.booktxt.net'.$opt->value;
					$temp = array(
				    	'url' => $link,
				    	'book_info_id' => $book_info_id,
						'des' => $des,
						'status' => 'wait'
				    );
				    $empty = Capsule::table('temp_booktxt_menu_page')->where('url',$link)->get()->isEmpty();
				    if($empty) Capsule::table('temp_booktxt_menu_page')->insert($temp);
				}

				// 解析获取所有的章节列表
		        $t = array('status'=>'wait','book_info_id'=>$book_info_id);
				$empty=Capsule::table('temp_booktxt_menu_page')->where($t)->get()->isEmpty();
				while (!$empty) {
					$datas=Capsule::table('temp_booktxt_menu_page')->where($t)->orderBy('id')->limit(50)->get();
					$temp = array();
					foreach ($datas as $key => $data) {
						$temp[] = array(
							'url' => $data->url,
							'file' => APP_DOWN.'temp_booktxt_menu_page/'.$data->id.'.html',
						);
					}
					@mkdir(APP_DOWN.'temp_booktxt_menu_page', 0777, true);
					$Download->pool($temp);


					foreach ($datas as $key => $data) {
						$file = APP_DOWN.'temp_booktxt_menu_page/'.$data->id.'.html';
						// 判定是否已经存在且合法
				    	if (file_exists($file))
				    	{
				    		// 字符编码转换
							$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
				    		if($dom = HtmlDomParser::str_get_html($html))
							{
								$tmp = basename($data->url,".html");
								if(strpos($tmp,'-')){
									$tmp = explode('-', $tmp);
									$page = end($tmp);
								}else{
									$page = 1;
								}
								foreach ($dom->find('.directoryArea',1)->find('a') as $kk => $a)
								{
									$menu_name = $a->innertext;
									$url = 'https://m.booktxt.net'.$a->href;
									$temp = array(
										'url' => $url,
										'book_info_id' => $book_info_id,
										'menu_name' => $menu_name,
										'status'=>'wait',
										'menu_id' => ($page-1)*20 + ($kk+1)
									);
									$empty = Capsule::table('temp_booktxt_menu_list')->where('url',$url)->get()->isEmpty();
				    				if($empty) Capsule::table('temp_booktxt_menu_list')->insert($temp);
								}
					            Capsule::table('temp_booktxt_menu_page')->where('id', $data->id)->update(['status' =>'readed']);
					            echo 'temp_booktxt_menu_page '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					            logdebug('temp_booktxt_menu_page '.$data->id.'.html'."  analyse successful!",'booktxt');
							}
				    	}
					}
		            echo 'temp_booktxt_menu_page '.$data->id.'.html'."  analyse successful!".PHP_EOL;
		            logdebug('temp_booktxt_menu_page '.$data->id.'.html'."  analyse successful!",'booktxt');
		            $empty=Capsule::table('temp_booktxt_menu_page')->where($t)->get()->isEmpty();
				}
				
				// 下载章节
		        $t = array('status'=>'wait','book_info_id'=>$book_info_id);
				$empty=Capsule::table('temp_booktxt_menu_list')->where($t)->get()->isEmpty();

				while (!$empty) {
					$datas=Capsule::table('temp_booktxt_menu_list')->where($t)->orderBy('id')->limit(50)->get();
					$temp = array();
					foreach ($datas as $key => $data) {
						$temp[] = array(
							'url' => $data->url,
							'file' => APP_DOWN.'temp_booktxt_menu_list/'.$data->id.'.html',
						);
					}
					@mkdir(APP_DOWN.'temp_booktxt_menu_list', 0777, true);
					$Download->pool($temp);

					// 解析入库章节内容
					foreach ($datas as $key => $data) {
						$file = APP_DOWN.'temp_booktxt_menu_list/'.$data->id.'.html';
						if (file_exists($file))
				    	{
				    		// 字符编码转换
							$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
							// dom解析
				    		if($dom = HtmlDomParser::str_get_html($html))
							{
								$menu_detail = $dom->find('#chaptercontent',0)->innertext;
								$menu_detail = preg_replace('/<p[\s\S]+<\/p>/iU', '', $menu_detail);
								$menu_detail = html_entity_decode($menu_detail);
								$menu_detail=str_replace('§§§§','§§',str_replace('<br />','§§',$menu_detail));

								$temp=array(
									'book_id' => $book_info_id,
									'menu_content'=> trim($menu_detail),
									'menu_id' => $data->menu_id,
									'menu_name' => $data->menu_name,
								);
								// 保存章节信息
								$Save->saveBookMenu($temp);
								logdebug('temp_booktxt_menu_list '.$data->id.'.html'."  analyse successful!",'booktxt');
					            echo 'temp_booktxt_menu_list '.$data->id.'.html'."  analyse successful!".PHP_EOL;
							}
							Capsule::table('temp_booktxt_menu_list')->where('id', $data->id)->update(['status' =>'readed']);
				    	}
					}
					$empty=Capsule::table('temp_booktxt_menu_list')->where($t)->get()->isEmpty();
				}
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
    	
		$empty = Capsule::table('temp_booktxt_book_link')->where($t)->get()->isEmpty();
		while(!$empty) {
			$datas=Capsule::table('temp_booktxt_book_link')->where($t)->orderBy('id')->limit(10)->get();
		    foreach ($datas as $data)
		    {
		    	$this->updateBook($data);
		    	Capsule::table('temp_booktxt_book_link')->where('id',$data->id)->update(array('status'=>'completed'));
		    }
		    $empty = Capsule::table('temp_booktxt_book_link')->where($t)->get()->isEmpty();
		}

	}

}