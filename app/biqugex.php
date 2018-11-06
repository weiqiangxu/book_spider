<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Sunra\PhpSimple\HtmlDomParser;

/**
  * 笔趣阁 -爬虫脚本
  */
class biqugex implements AppFactory {

	// 检测状态记录表
	public function initable()
	{
		// 书籍列表-更新时候检测是否已经查过[依据路由地址]
		if(!Capsule::schema()->hasTable('temp_biquge_book_list'))
		{
			Capsule::schema()->create('temp_biquge_book_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable()->index();
			    $table->string('status')->nullable();
			});
			echo "table temp_biquge_book_list create".PHP_EOL;
		}

		// 书籍章节列表-更新时候检测是否已经查过[依据路由地址]
		if(!Capsule::schema()->hasTable('temp_biquge_menu_list'))
		{
			Capsule::schema()->create('temp_biquge_menu_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('book_info_id')->nullable();
			    $table->string('menu_name')->nullable();
			    $table->string('menu_id')->nullable();
			});
			echo "table temp_biquge_menu_list create".PHP_EOL;
		}
	}


	// 检测是否有新书籍
	public function book_link()
	{
		$Download = new Download();
		@mkdir(APP_DOWN.'temp_biquge_book_list', 0777, true);
		$file = APP_DOWN.'temp_biquge_book_list/index.html';
		$Download->down('https://www.biqugex.com/paihangbang/',$file);
		$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");// dom解析
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('.block') as $block_key => $block)
			{
				if($block_key==0){
					continue;
				}
				foreach($block->find('li') as $k => $li)
				{

					$url = 'https://www.biqugex.com'.$li->find('a',0)->href;
					$temp = [
				    	'url' => $url,
				    	'status'=>'wait',
				    ];
				    $res = Capsule::table('temp_biquge_book_list')->where('url',$url)->first();
			    	if(empty($res)){
						echo 'add '.$url.PHP_EOL;
						logdebug('add '.$url,'biqugex');
			    		Capsule::table('temp_biquge_book_list')->insert($temp);
			    	}else{
			    		echo 'update '.$url.PHP_EOL;
			    		logdebug('update '.$url,'biqugex');
			    		Capsule::table('temp_biquge_book_list')->where('id',$res->id)->update(array('status'=>'wait'));
			    	}
				}
			}
		}
	}


	// 循环遍历book_link表 - 更新|新增章节、书籍基本信息
	public function updateBook($book_list)
	{
		// 下载
		$Download = new Download();
		$Save = new Save();
		@mkdir(APP_DOWN.'temp_biquge_book_list', 0777, true);
		echo 'download book_list'.$book_list->id.'.html'.PHP_EOL;
		logdebug('download book_list'.$book_list->id.'.html','biqugex');
		$file = APP_DOWN.'temp_biquge_book_list/'.$book_list->id.'.html';
		$Download->down($book_list->url,$file);
		$website_id = 9;

    	// 判定是否已经存在且合法
    	if (file_exists($file))
    	{
    		// 字符编码转换
			$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
			@unlink($file);
	    	// 正则去除什么更新章节
	    	$html=preg_replace('/class="listmain">[\S\s]+正文卷[\s]*<\/dt>/iU', 'class="listmain">', $html);
			// dom解析
    		if($dom = HtmlDomParser::str_get_html($html))
			{
				$book_name=$dom->find(".cover",0)->next_sibling()->innertext;
				$author_name=$dom->find('.small',0)->children(0)->innertext;
				$type=$dom->find('.small',0)->children(1)->innertext;
				$book_status = $dom->find('.small',0)->children(2)->innertext;
				$uptime = $dom->find('.small',0)->find('.last',0)->innertext;
				$instruct=$dom->find('.intro',0)->innertext;
				$instruct=strip_tags($instruct);
				$instruct=preg_replace('/无弹窗推荐地址[\s\S]+$/i', '', $instruct);
				$cover=$dom->find(".cover",0)->find('img',0)->src;

				$cpbookid = str_replace('https://www.biqugex.com/book_', '',$book_list->url);
				$cpbookid = str_replace('/', '', $cpbookid);

				$temp=array(
					'type' => str_replace('分类：', '', $type),
					'book_name' => trim($book_name),
					'author_name' => str_replace('作者：','',trim($author_name)),
					'updatetime' =>str_replace('更新时间：', '', trim($uptime)),
					'instruct' => str_replace('简介：', '', $instruct),
					'book_status' => str_replace('状态：', '', trim($book_status)),
					'image_url' => 'https://www.biqugex.com'.$cover,
					'website_id' => $website_id,
					'tag' => str_replace('分类：', '', $type),
					'cpbookid' => $cpbookid,
				);

				$book_info_id = $Save->saveBookInfo($temp);

				foreach($dom->find('.listmain',0)->find('a') as $kk=> $a)
				{
					$url = 'https://www.biqugex.com'.$a->href;
					$temp=array(
						'url'=> $url,
						'book_info_id'=>$book_info_id,
						'menu_name'=>$a->innertext,
						'menu_id'=>$kk+1,
						'status'=>'wait'		
					);
					$empty = Capsule::table('temp_biquge_menu_list')->where('url',$url)->get()->isEmpty();
			    	if($empty){
						echo $url.PHP_EOL;
						logdebug($url,'biqugex');
			    		Capsule::table('temp_biquge_menu_list')->insert($temp);
						echo 'biquge temp_biquge_menu_list '.($kk+1).' insert success'.PHP_EOL;
						logdebug('biquge temp_biquge_menu_list '.($kk+1).' insert success','biqugex');
			    	}else{
			    		echo $url.' has analysed!'.PHP_EOL;
			    		logdebug($url.' has analysed!','biqugex');
			    	}
				}

	            // 下载章节内容
	            $t = array('status'=>'wait','book_info_id'=>$book_info_id);
				$empty=Capsule::table('temp_biquge_menu_list')->where($t)->get()->isEmpty();

				while (!$empty) {
					
					$datas=Capsule::table('temp_biquge_menu_list')->where($t)->orderBy('id')->limit(50)->get();
					$temp = array();
					foreach ($datas as $key => $data) {
						$temp[] = array(
							'url' => $data->url,
							'file' => APP_DOWN.'temp_biquge_menu_list/'.$data->id.'.html',
						);
					}
					@mkdir(APP_DOWN.'temp_biquge_menu_list', 0777, true);
					$Download->pool($temp);

					// 解析
					foreach ($datas as $data) {
						$file = APP_DOWN.'temp_biquge_menu_list/'.$data->id.'.html';
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
									'book_id' => $data->book_info_id,
									'menu_content'=> preg_replace('/https:\/\/www.biqugex.com[\S\s]+$/','',$content) ,
									'menu_id' => $data->menu_id,
									'menu_name' => $data->menu_name,
								);
								// 保存章节信息
								$Save->saveBookMenu($temp);
							}
							echo 'biquge temp_biquge_menu_list/'.$data->id.'.html analyse!'.PHP_EOL;
							logdebug('biquge temp_biquge_menu_list/'.$data->id.'.html analyse!','biqugex');
							Capsule::table('temp_biquge_menu_list')->where('id', $data->id)->update(['status' =>'readed']);
							@unlink($file);
						}
					}
					$empty=Capsule::table('temp_biquge_menu_list')->where($t)->get()->isEmpty();
				}
			}
    	}else{
    		echo 'temp_biquge_menu_list error!';
    		logdebug('temp_biquge_menu_list error!','biqugex');
    	}
	}


	public function start()
	{
		$this->initable();

		$this->book_link();
		
		$t = array(
			['status', '=', 'wait'],
		);
		$empty = Capsule::table('temp_biquge_book_list')->where($t)->get()->isEmpty();
		while(!$empty) {
			$datas=Capsule::table('temp_biquge_book_list')->where($t)->orderBy('id')->limit(10)->get();
		    foreach ($datas as $data)
		    {
		    	$this->updateBook($data);
		    	Capsule::table('temp_biquge_book_list')->where('id',$data->id)->update(array('status'=>'completed'));
		    }
		    $empty = Capsule::table('temp_biquge_book_list')->where($t)->get()->isEmpty();
		}

	}

}