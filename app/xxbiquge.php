<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Sunra\PhpSimple\HtmlDomParser;

/**
  * 新笔趣阁 -爬虫脚本
  */
class xxbiquge implements AppFactory {

	// 检测状态记录表
	public function initable()
	{
		// 书籍列表
		if(!Capsule::schema()->hasTable('temp_xxbiquge_book_link'))
		{
			Capsule::schema()->create('temp_xxbiquge_book_link', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable()->index();
			    $table->string('status')->nullable();
			});
			echo "table temp_xxbiquge_book_link create".PHP_EOL;
		}

		// 书籍章节列表
		if(!Capsule::schema()->hasTable('temp_xxbiquge_menu_list'))
		{
			Capsule::schema()->create('temp_xxbiquge_menu_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('book_info_id')->nullable();
			    $table->string('menu_name')->nullable();
			    $table->string('menu_id')->nullable();
			});
			echo "table temp_xxbiquge_menu_list create".PHP_EOL;
		}
	}


	// 检测是否有新书籍
	public function book_link()
	{
		$Download = new Download();
		@mkdir(APP_DOWN.'temp_xxbiquge_book_link', 0777, true);
		// 解析排行榜
		$file = APP_DOWN.'temp_xxbiquge_book_link/paihangbang.html';
		$Download->down('https://www.xxbiquge.com/xbqgph.html',$file);
		$html = file_get_contents($file);// dom解析
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('.novelslist2',0)->find('li') as $li_key => $li)
			{
				if($li_key==0){
					continue;
				}
				foreach($li->find('.s2',0)->find('a') as $k => $a)
				{
					$url = 'https://www.xxbiquge.com'.$a->href;
					echo $url.PHP_EOL;
					logdebug($url,'xxbiquge');
					$temp = [
				    	'url' => $url,
				    	'status'=>'wait',
				    ];
				    $empty = Capsule::table('temp_xxbiquge_book_link')->where('url',$url)->get()->isEmpty();
			    	if($empty) Capsule::table('temp_xxbiquge_book_link')->insert($temp);
				}
			}
		}

		// 解析首页
		$file = APP_DOWN.'temp_xxbiquge_book_link/index.html';
		$Download->down('https://www.xxbiquge.com/',$file);
		$html = file_get_contents($file);// dom解析
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('#main',0)->find('a') as $a)
			{
				// 正则检测 - 只要是书的链接统统拿过来
				$is_book = preg_match('/^\/\d{1,2}_\d{1,10}\/$/',$a->href);
				if($is_book){
					$url = 'https://www.xxbiquge.com'.$a->href;
					echo $url.PHP_EOL;
					logdebug($url,'xxbiquge');
					$temp = [
				    	'url' => $url,
				    	'status'=>'wait',
				    ];
				    $res = Capsule::table('temp_xxbiquge_book_link')->where('url',$url)->first();
			    	if(empty($res)){
						echo 'add '.$url.PHP_EOL;
						logdebug('add '.$url,'xxbiquge');
			    		Capsule::table('temp_xxbiquge_book_link')->insert($temp);
			    	}else{
			    		echo 'update '.$url.PHP_EOL;
			    		logdebug('update '.$url,'xxbiquge');
			    		Capsule::table('temp_xxbiquge_book_link')->where('id',$res->id)->update(array('status'=>'wait'));
			    	}
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
		@mkdir(APP_DOWN.'temp_xxbiquge_book_link', 0777, true);
		echo 'download temp_xxbiquge_book_link'.$book_link->id.'.html'.PHP_EOL;
		logdebug('download temp_xxbiquge_book_link'.$book_link->id.'.html','xxbiquge');
		$file = APP_DOWN.'temp_xxbiquge_book_link/'.$book_link->id.'.html';
		$Download->down($book_link->url,$file);
		$website_id = 8;

    	// 判定是否已经存在且合法
    	if (file_exists($file))
    	{
    		// 字符编码转换
			$html = file_get_contents($file);
			@unlink($file);
			// dom解析
    		if($dom = HtmlDomParser::str_get_html($html))
			{

				foreach ($dom->find("#maininfo",0)->find("#info",0)->find('p') as $p) {
					if(preg_match('/作&nbsp;&nbsp;&nbsp;&nbsp;者/', $p->innertext)){
						$author_name = trim(str_replace('作&nbsp;&nbsp;&nbsp;&nbsp;者：','',$p->innertext));
					}
					if(preg_match('/状&nbsp;&nbsp;&nbsp;&nbsp;态/', $p->innertext)){
						$book_status= trim(strip_tags(str_replace('状&nbsp;&nbsp;&nbsp;&nbsp;态','',$p->innertext)));
					}
					if(preg_match('/最后更新/', $p->innertext)){
						$uptime=trim(str_replace('最后更新：','',$p->innertext));
					}
				}

				$book_name=$dom->find("#info",0)->find('h1',0)->innertext;
				$type=$dom->find('.con_top',0)->find('a',1)->innertext;
				$instruct=$dom->find('#intro',0)->find('p',0)->innertext;
				$cover=$dom->find("#fmimg",0)->find('img',0)->src;
				preg_match('/_(\d+)\/$/', $book_link->url,$cpbookid);
				$cpbookid = $cpbookid[1];
				$temp=array(
					'type' => trim($type),
					'book_name' => trim($book_name),
					'author_name' => $author_name,
					'updatetime' =>$uptime,
					'instruct' => trim($instruct),
					'book_status' => $book_status,
					'image_url' => $cover,
					'website_id' => $website_id,
					'tag' => trim($type),
					'cpbookid' => $cpbookid,
				);

				$book_info_id = $Save->saveBookInfo($temp);
				echo 'xxbiquge book_info '.$book_link->id.' save success'.PHP_EOL;
				logdebug('xxbiquge book_info '.$book_link->id.' save success','xxbiquge');
				// 获取所有的章节
				foreach($dom->find('#list',0)->find('a') as $kk=> $a)
				{
					$temp=array(
						'url'=>'https://www.xxbiquge.com'.$a->href,
						'book_info_id'=>$book_info_id,
						'menu_name'=>$a->innertext,
						'menu_id'=>$kk+1,
						'status'=>'wait',	
					);
					echo 'temp_xxbiquge_menu_list '.($kk+1).' insert success'.PHP_EOL;
					logdebug('temp_xxbiquge_menu_list '.($kk+1).' insert success','xxbiquge');
					Capsule::table('temp_xxbiquge_menu_list')->insert($temp);
				}

	            // 下载章节列表并解析内容
				$t = array('status'=>'wait','book_info_id'=>$book_info_id);
				$empty=Capsule::table('temp_xxbiquge_menu_list')->where($t)->get()->isEmpty();

				while (!$empty) {
					
				$datas=Capsule::table('temp_xxbiquge_menu_list')->where($t)->orderBy('id')->limit(50)->get();
				$temp = array();
				foreach ($datas as $key => $data) {
					$temp[] = array(
						'url' => $data->url,
						'file' => APP_DOWN.'temp_xxbiquge_menu_list/'.$data->id.'.html',
					);
				}
				@mkdir(APP_DOWN.'temp_xxbiquge_menu_list', 0777, true);
				$Download->pool($temp);

					// 解析
					foreach ($datas as $data) {
						$file = APP_DOWN.'temp_xxbiquge_menu_list/'.$data->id.'.html';
						if (file_exists($file))
				    	{
				    		// 字符编码转换
							$html = file_get_contents($file);
							// dom解析
				    		if($dom = HtmlDomParser::str_get_html($html))
							{
								$content = $dom->find("#content",0)->innertext;
								$content = html_entity_decode($content);
								$content=str_replace('§§§§','§§',str_replace('<br />','§§',$content));

								$temp=array(
									'book_id' => $data->book_info_id,
									'menu_content'=> trim($content),
									'menu_id' => $data->menu_id,
									'menu_name' => $data->menu_name,
								);
								// 保存章节信息
								$Save->saveBookMenu($temp);
							}
							echo 'temp_xxbiquge_menu_list/'.$data->id.'.html analyse!'.PHP_EOL;
							logdebug('temp_xxbiquge_menu_list/'.$data->id.'.html analyse!','xxbiquge');
							Capsule::table('temp_xxbiquge_menu_list')->where('id', $data->id)->update(['status' =>'readed']);
						}
					}
					$empty=Capsule::table('temp_xxbiquge_menu_list')->where($t)->get()->isEmpty();
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
		$empty = Capsule::table('temp_xxbiquge_book_link')->where($t)->get()->isEmpty();
		while(!$empty) {
			$datas=Capsule::table('temp_xxbiquge_book_link')->where($t)->orderBy('id')->limit(10)->get();
		    foreach ($datas as $data)
		    {
		    	$this->updateBook($data);
		    	Capsule::table('temp_xxbiquge_book_link')->where('id',$data->id)->update(array('status'=>'completed'));
		    }
		    $empty = Capsule::table('temp_xxbiquge_book_link')->where($t)->get()->isEmpty();
		}

	}

}