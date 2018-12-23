<?php
class controller
{
	/************************************
	 *
	 *	初期化
	 *
	 ************************************/
	function controller()
	{
		global $ini_data,$post_data,$get_data,$cookie_data;
		$post_name   = array("number","name","mail","url","title","com","key","cookie","pre","page","action");
		$get_name    = array("mode","page","no","quote","type","srch");
		$cookie_name = array("name","mail","url","key","cookie");
		$ini_data    = array();
		$post_data   = array();
		$get_data    = array();
		$cookie_data = array();
		
		$ini_data = parse_ini_file("config.ini");
		foreach($ini_data as $key => $val){define($key,$val);}
		foreach($post_name as $key)
		{
			if(isset($_POST[$key]))
			{
				$post_data[$key] = $_POST[$key];
			}else{
				$post_data[$key] = NULL;
			}
		}
		foreach($get_name as $key)
		{
			if(isset($_GET[$key]))
			{
				$get_data[$key] = $_GET[$key];
			}else{
				$get_data[$key] = NULL;
			}
		}
		foreach($cookie_name as $key)
		{
			if(isset($_COOKIE[$key]))
			{
				$cookie_data[$key] = $_COOKIE[$key];
			}else{
				$cookie_data[$key] = NULL;
			}
		}
		if(!is_file(topic_log))
		{
			touch(topic_log);
		}
		if(!is_file(res_log))
		{
			touch(res_log);
		}
		if(!is_file(count_log))
		{
			touch(count_log);
		}
		if(!is_file(lock_file))
		{
			touch(lock_file);
		}
	}
	
	/************************************
	 *
	 *	トピック表示
	 *
	 ************************************/
	function topic_view()
	{
		// 初期値
		global $ini_data,$get_data;
		$topic = array();
		$total = 0;
		$j     = 0;
		// チェック
		if(!preg_match("/^[0-9]*$/", $get_data["page"]))
		{
			$this -> html_error("番号が不明です");
			return;
		}
		// ページの取得
		if(!$get_data["page"])
		{
			$point = 0;
		}else{
			$point = $get_data["page"] * $ini_data["topic_num"];
		}
		
		// ログファイル及びログ最大数の取得
		$topic = file($ini_data["topic_log"]);
		$total = $topic ? count($topic) : 0;
		
		// トピックの表示
		$this -> html_start();

		if ($total === 0) {
			echo "トピックがありません\n";
			return;
		}
		echo "<table class=\"style\">\n";
		for($i=$point; $i < $total; ++$i)
		{
			if($j++ >= $ini_data["topic_num"])
			{
				break;
			}
			if(($i-$point)%10 == 0)
			{
				$this -> html_topic_head();
			}
			$data = explode(",", $topic[$i]);
			$this -> html_topic_body($data);
		}
		echo "</table>\n";
		// ページリンク
		if($total > $ini_data["topic_num"])
		{
			echo "<p>";
			if($get_data["page"])
			{
				$num = $get_data["page"]-1;
				echo "<a href=\"./".bbs_name."?page={$num}\" title=\"前のページ\"><<</a>&nbsp;&nbsp;";
			}
			//ダイレクトリンク
			$j = $total / $ini_data["topic_num"];
			for($i=0; $i <= $j; ++$i)
			{
				if($i != $get_data["page"])
				{
					echo "<a href=\"./".bbs_name."?page={$i}\" title=\"ダイレクトリンク\">[$i]</a>&nbsp;";
				}else{
					echo "[$i]&nbsp;";
				}
			}
			if($total-$ini_data["topic_num"] > $get_data["page"]*$ini_data["topic_num"])
			{
				$num = $get_data["page"]+1;
				echo "&nbsp;<a href=\"./".bbs_name."?page={$num}\" title=\"次のページ\">>></a>";
			}
			echo "</p>\n";
		}
	}
	
	// HTML表記 テーブルヘッダー
	function html_topic_head()
	{
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<th>トピックタイトル</th>\n";
		echo "<th>最終更新</th>\n";
		echo "<th>記事数</th>\n";
		echo "<th>トピック作成者</th>\n";
		echo "<th>最終投稿者</th>\n";
		echo "<th></th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
	}
	
	// HTML表記 テーブルボディー
	function html_topic_body($data)
	{
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td>{$this->new_flag($data[13])}</td>\n";
		echo "<td style=\"text-align:left;\"><a href=\"./".bbs_name."?mode=res&no={$data[0]}\" style=\"font-size:140%;font-weight:bold;\">{$data[3]}</a><div>{$this->substr($data[7])}</div></td>\n";
		echo "<td nowrap align=\"center\">{$this->days($data[13])}</td>\n";
		echo "<td>{$this->res_count($data[12])}</td>\n";
		echo "<td>$data[4]</td>\n";
		echo "<td>{$data[11]}</td>\n";
		echo "<td></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}
	
	// 時間の変換（ 年/月/日（曜日）時:分 ）
	function days($time)
	{
		$week = array("日","月","火","水","木","金","土");
		$days = date("Y/m/d", $time);
		$days.= "(".$week[date("w", $time)].") ";
		$days.= date("H:i", $time);
		
		return $days;
	}
	
	// 新着の表記チェック
	function new_flag($time)
	{
		global $ini_data;
		
		if($time + (60*60*$ini_data["new_time"]) > time())
		{
			return $ini_data["new_type"];
		}
		
		return NULL;
	}
	
	// 引用コメント（75文字カット）
	function substr($str)
	{
		$str = str_replace("<br>", "", $str);
		if(strlen($str) > 75)
		{
			return substr($str, 0 , 75)."....";
		}
		
		return $str;
	}
	
	// 記事数の取得
	function res_count($num)
	{
		return count(explode("<>", $num)) - 1;
	}
	
	/************************************
	 *
	 *	記事表示
	 *
	 ************************************/
	function res_view()
	{
		// 初期値
		global $get_data;
		$view_data = array();
		$name_list = array();
		$take = 0;
		$take = 0;
		$quote = null;
		
		// 番号の正当性をチェック
		if(!preg_match("/^[0-9]+$/", $get_data["no"]))
		{
			$this -> html_error("番号が不明です");
			return;
		}
		
		// 指定のトピックを読み込む
		$fp = fopen(topic_log, "r");
		while($str = fgets($fp))
		{
			$data = explode(",", $str);
			if($data[0] == $get_data["no"])
			{
				$view_data[] = $data;
				$name_list[] = "<a href=\"#{$data[0]}\" title=\"記事No:{$data[0]}\">[{$data[0]}]&nbsp;{$data[4]}</a><br>\n";
				if($data[0] == $get_data["quote"])
				{
					$data[7] = preg_replace("/<br>/i","\n> ", $data[7]);
					$quote = "> ".$data[7];
				}
				break;
			}
		}
		fclose($fp);
		
		// トピックの記事を読み込む
		$fp = fopen(res_log, "r");
		while($str = fgets($fp))
		{
			$data = explode(",", $str);
			if($data[1] == $get_data["no"] || $data[0] == $get_data["no"] )
			{
				if(!$take)
				{
					$take = 1;	// 記事列を見つけた事を示す
				}
				if($data[0] == $get_data["quote"])
				{
					$data[7] = preg_replace("/<br>/i","\n> ", $data[7]);
					$quote = "> ".$data[7];
				}
				
				$view_data[] = $data;
				$name_list[] = "・<a href=\"#{$data[0]}\" title=\"記事No:{$data[0]}\">[{$data[0]}]&nbsp;{$data[4]}</a><br>\n";
			}
			// 早々に閉じる
			else if($take)
			{
				break;
			}
		}
		fclose($fp);
		
		// 記事が見つからなかったらエラーを出し終了
		if(!$view_data)
		{
			$this -> html_error("記事がみつかりませんでした");
			return;
		}
		
		/* 記事の表示 */
		$this -> html_start();
		echo "<table class=\"topic\">\n";
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th colspan=\"2\">{$view_data[0][3]}</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td class=\"td1\">\n";
		//記事リンク
		foreach($name_list as $val)
		{
			echo $val;
		}
		echo "</td>\n";
		echo "<td class=\"td2\">\n";
		echo "<table class=\"style\" style=\"width:100%;\">\n";
		// トピック
		$this -> html_topic($view_data[0]);
		echo "</table>\n";
		echo "<img src=\"./img/line.png\" border=\"0\">\n";
		echo "<table class=\"style\" style=\"width:100%;\">\n";
		// トピックの記事
		$total = count($view_data);
		for($i=1; $i < $total; ++$i)
		{
			$this -> html_res($view_data[$i]);
		}
		echo "</table>\n";
		
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		// 返信フォーム
		if(!$view_data[0][1] && max_num > $total)
		{
			$this -> html_form("Re:".$view_data[0][3], $quote);
		}
	}
	
	// 記事HTMLスタイル
	function html_topic($data)
	{
		$this -> sub_object($data);
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:left;border-right-width:0pt;\"><a name=\"{$data[0]}\"></a>No:{$data[0]}&nbsp;&nbsp;Name:&nbsp;<span style=\"font-size:110%;\">{$data[4]}</span>{$data[5]}</th>\n";
		echo "<th style=\"text-align:right;border-left-width:0pt;\">\n";
		echo "[<a href=\"#form\">返信</a>]\n";
		echo "[<a href=\"./".bbs_name."?mode=res&no={$data[0]}&quote={$data[0]}#form\">引用</a>]\n";
		echo "[<a href=\"./".bbs_name."?mode=key&no={$data[0]}\">編集</a>]</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td colspan=\"2\" style=\"text-align:left;\">\n";
		echo "<h2>Title:&nbsp;{$data[3]}</h2>\n";
		echo "<p>\n";
		echo "{$data[7]}";
		echo "</p>\n";
		echo "<div style=\"text-align:right;\">\n";
		echo "{$this->days($data[2])}";
		echo "</div>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}
	
	function html_res($data)
	{
		$this -> sub_object($data);
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:left;border-right-width:0pt;\"><a name=\"{$data[0]}\"></a>No:{$data[0]}&nbsp;&nbsp;Name:&nbsp;<span style=\"font-size:110%;\">{$data[4]}</span>{$data[5]}</th>\n";
		echo "<th style=\"text-align:right;border-left-width:0pt;\">\n";
		echo "[<a href=\"./".bbs_name."?mode=res&no={$data[1]}&quote={$data[0]}#form\">引用</a>]\n";
		echo "[<a href=\"./".bbs_name."?mode=key&no={$data[0]}\">編集</a>]</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td colspan=\"2\" style=\"text-align:left;border-width:0pt;\">\n";
		echo "<h2>Title:&nbsp;{$data[3]}</h2>\n";
		echo "<p>\n";
		echo "{$data[7]}";
		echo "</p>\n";
		echo "<div style=\"text-align:right;\">\n";
		echo "{$this->days($data[2])}";
		echo "</div>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}
	
	// HTML表記 エラー
	function html_error($str)
	{
		$this -> html_start();
		echo "<b style=\"color:red\">{$str}</b><br>\n";
	}
	
	// URL・Mailがあればリンク
	function sub_object(&$data)
	{
		if($data[5])
		{
			$data[5] = "&nbsp;[<a href=\"http://{$data[5]}\">URL</a>]";
		}
		if($data[6])
		{
			$data[4] = "<a href=\"ma&#105;lt&#111;&#x3a;{$data[6]}\">{$data[4]}</a>";
		}
	}
	
	/************************************
	 *
	 *	投稿フォーム
	 *
	 ************************************/
	function html_form($title = NULL, $quote = NULL)
	{
		global $ini_data,$get_data,$cookie_data;
		if(!$get_data["no"])
		{
			$get_data["no"] = 0;
		}
		echo "<a name=\"form\"></a>\n";
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=write\" onsubmit=\"return submitForm()\">\n";
		echo "<table class=\"form\">\n";
		echo "<tbody>\n";
		echo "<input type=\"hidden\" name=\"number\" value=\"{$get_data["no"]}\">\n";
		echo "<tr>\n";
		echo "<th>名前</th>\n";
		echo "<td><input type=\"text\" name=\"name\" size=\"50\" maxlength=\"{$ini_data["name_len"]}\" value=\"{$cookie_data["name"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>E-Mail</th>\n";
		echo "<td><input type=\"text\" name=\"mail\" size=\"50\" maxlength=\"{$ini_data["mail_len"]}\" value=\"{$cookie_data["mail"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>URL</th>\n";
		echo "<td><input type=\"text\" name=\"url\" size=\"50\" maxlength=\"{$ini_data["url_len"]}\" value=\"http://{$cookie_data["url"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>タイトル</th>\n";
		echo "<td><input type=\"text\" name=\"title\" size=\"50\" maxlength=\"{$ini_data["title_len"]}\" value=\"{$title}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>コメント</th>\n";
		echo "<td><textarea id=\"com\" name=\"com\" cols=60 rows=12>$quote</textarea></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>認証キー</th>\n";
		echo "<td><input type=\"password\" name=\"key\" size=\"10\" maxlength=\"8\" value=\"{$cookie_data["key"]}\">&nbsp;<small>(最大8文字)</small></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<td><input type=\"checkbox\" name=\"pre\" value=\"on\"><small>プレビュー</small>\n";
		echo "<input type=\"checkbox\" name=\"cookie\" value=\"checked\" {$cookie_data["cookie"]}><small>クッキー保存</small>\n";
		echo "&nbsp;&nbsp;<input type=\"submit\" value=\"書き込む\"></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	//プレビュー用
	function html_form_post()
	{
		global $ini_data,$post_data;
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=write\" onsubmit=\"return submitForm()\">\n";
		echo "<table class=\"form\">\n";
		echo "<tbody>\n";
		echo "<input type=\"hidden\" name=\"number\" value=\"{$post_data["number"]}\">\n";
		echo "<tr>\n";
		echo "<th>名前</th>\n";
		echo "<td><input type=\"text\" name=\"name\" size=\"50\" maxlength=\"{$ini_data["name_len"]}\" value=\"{$post_data["name"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>E-Mail</th>\n";
		echo "<td><input type=\"text\" name=\"mail\" size=\"50\" maxlength=\"{$ini_data["mail_len"]}\" value=\"{$post_data["mail"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>URL</th>\n";
		echo "<td><input type=\"text\" name=\"url\" size=\"50\" maxlength=\"{$ini_data["url_len"]}\" value=\"{$post_data["url"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>タイトル</th>\n";
		echo "<td><input type=\"text\" name=\"title\" size=\"50\" maxlength=\"{$ini_data["title_len"]}\" value=\"{$post_data["title"]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>コメント</th>\n";
		echo "<td><textarea id=\"com\" name=\"com\" cols=60 rows=12>{$post_data["com"]}</textarea></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>認証キー</th>\n";
		echo "<td><input type=\"password\" name=\"key\" size=\"10\" maxlength=\"8\" value=\"{$post_data["key"]}\">&nbsp;<small>(最大8文字)</small></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<td><input type=\"checkbox\" name=\"pre\" value=\"on\"><small>プレビュー</small>\n";
		echo "<input type=\"checkbox\" name=\"cookie\" value=\"checked\" {$post_data["cookie"]}><small>クッキー保存</small>\n";
		echo "&nbsp;&nbsp;<input type=\"submit\" value=\"書き込む\"></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	/************************************
	 *
	 *	ログ書き込み
	 *
	 ************************************/
	function write_controll()
	{
		// 初期化
		global $post_data;
		
		// フォームデータのチェックおよび加工
		$err_mge = $this -> form_check();
		$str = $this -> from_convert($post_data);
		$data = explode(",", $str);
		
		// エラーもしくはプレビューにチェックがあれば終了
		if($err_mge)
		{
			$data[0] = "エラー";
			$this -> html_error($err_mge);
			echo "<table class=\"style\">\n";
			$this -> html_topic($data);
			echo "</table>\n";
			$this -> html_form_post();
			
			return;
		}else if($post_data["pre"]){
			$this -> html_start();
			$data[0] = "プレビュー";
			echo "<table class=\"style\">\n";
			$this -> html_topic($data);
			echo "</table>\n";
			$this -> html_form_post();
			
			return;
		}
		/* 記事の書き込み */
		if($post_data["number"])
		{
			$this -> write_res($str);
			if($post_data["cookie"])
			{
				$this -> set_cookie();
			}
		}else{
			$this -> write_topic($str);
			if($post_data["cookie"])
			{
				$this -> set_cookie();
			}
		}
		$this -> article_number_up(0);	//記事番号のカウントアップ
		header("Location:".bbs_dir.bbs_name);
	}
	
	// フォームデータの正当性のチェック
	function form_check()
	{
		global $post_data;
		if(!$post_data["title"])
			return "タイトル未記入";
		else if(!$post_data["name"])
			return "名前未記入";
		else if(!$post_data["com"])
			return "コメント未記入";
		else if(strlen($post_data["title"]) > title_len)
			return "タイトルが長すぎます";
		else if(strlen($post_data["name"]) > name_len)
			return "名前が長すぎます";
		else if(strlen($post_data["url"]) > url_len)
			return "URLが長すぎます";
		else if(strlen($post_data["mail"]) > mail_len)
			return "E-mailが長すぎます";
		else if(strlen($post_data["com"]) > comment_len)
			return "コメントが長すぎます";
		//else if($post_data["name"] === master_name && $post_data["key"] !== master_pass)
		//	return "管理人名は使えません";
		else if(!preg_match("/^[0-9]+$/", $post_data["number"]))
			return "記事番号が不正です";
		else if(!preg_match("/^([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$|^$|^$/i", $post_data["mail"]))
			return "E-Mailが不正です";
		else if(!preg_match("/^http:\/\/|^$/i", $post_data["url"]))
			return "URLが不正です";
		return NULL;
	}
	
	// フォームデータの加工
	function from_convert($post_data)
	{
		foreach($post_data as $key => $val){$post_data[$key] = $this->str_convert($val);}
		$this -> convert($post_data);
		
		$num = $this->article_number_get(0);
		
		return "{$num},{$post_data["number"]},".time().",{$post_data["title"]},{$post_data["name"]},{$post_data["url"]},{$post_data["mail"]},{$post_data["com"]},{$post_data["key"]},".gethostbyaddr($_SERVER["REMOTE_ADDR"]).",{$_SERVER["REMOTE_ADDR"]},-,<>,,,,\n";
	}
	// 変換
	function convert(&$post_data)
	{
		$post_data["url"]  = str_replace( "http://", "", $post_data["url"]);
		$post_data["mail"] = str_replace( "@", "&#64;", $post_data["mail"]);
		if(url_flag)
		{
			$post_data["com"] = preg_replace( "/&lt;a .+&gt;(.+)&lt;\/a&gt;/i", "$1", $post_data["com"]);
			$post_data["com"] = preg_replace( "/(http:\/\/[\w\.\/\-=&%?;#]+)/i", "<a href=\"$1\" target=\"_blank\">$1</a>", $post_data["com"]);
		}
		/*if(tag_member)
		{
			$post_data["com"] = preg_replace( "/&lt;(".tag_member.")&gt;(.*?)&lt;(\/".tag_member.")&gt;/i","<$1>$2<$3>",$post_data["com"]);
		}*/
		$post_data["key"]  = crypt($post_data["key"]);
	}
	
	// フォームデータの加工2
	function str_convert($str)
	{
		$str = str_replace("\\", "\\\\", $str);
		$str = str_replace( ",", "，", $str);
		$str = stripslashes($str);
		$str = htmlspecialchars($str, ENT_QUOTES);
		$str = preg_replace("/()\n|\r|\r\n)/","<br>", $str);
		return $str;
	}
	
	// 記事番号の取得
	function article_number_get($i)
	{
		$fp = fopen(count_log, "r") or die($this->html_error("ファイルが開けません"));
		flock($fp,LOCK_EX);
		$str = fgets($fp);
		fclose($fp);
		$num = explode(",", $str);
		++$num[$i];
		return $num[$i];
	}
	
	// 記事番号カウントアップ
	function article_number_up($i)
	{
		$fp = fopen(count_log, "r") or die($this->html_error("ファイルが開けません"));
		flock($fp,LOCK_EX);
		$str = fgets($fp);
		fclose($fp);
		$num = explode(",", $str);
		++$num[$i];
		$fp = fopen(count_log, "w") or die($this->html_error("ファイルが開けません"));
		flock($fp,LOCK_EX);
		fwrite($fp,"{$num[0]},{$num[1]},{$num[2]},{$num[3]},{$num[4]},\n");
		fclose($fp);
	}
	
	// トピックログの書き込み
	function write_topic($str)
	{
		$writ_data  = explode(",", $str);
		
		$writ_data[13] = time();	// 最終更新日の追加
		$str = implode(",", $writ_data);
		
		$fl = fopen(lock_file, "w") or die($this->html_error("ファイルが開けません"));
		flock($fl,LOCK_EX);
		$topic_data = file(topic_log);
		array_unshift($topic_data, $str);
		if(count($topic_data) > max_log)
		{
			$this -> topic_make($topic_data);
			print "実行完了";
		}
		
		$fp = fopen(topic_log, "w");
		foreach($topic_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		fclose($fl);
	}
	
	// 過去ログ作成
	function topic_make(&$topic_data)
	{
		$topic_log_data = array();
		$res_log_data = array();
		$data = array();
		$article = $this->article_number_get(1);
		
		for($i=0; $i<max_memory; ++$i)
		{
			$topic_log_data[$i] = array_pop($topic_data);
			preg_match("/^([0-9]+),/",$topic_log_data[$i],$num);
			$data[$num[1]] = $num[1];
		}
		
		$fp = fopen(res_log, "r");	//レスの切り抜き
		while($buf = fgets($fp))
		{
			$str = explode(",", $buf);
			if(isset($data[$str[1]]) && $data[$str[1]] == $str[1])
			{
				$res_log_data[] = $buf;	//過去ログへ
			}else{
				$res_data[] = $buf;
			}
		}
		fclose($fp);
		
		$fp = fopen(res_log, "w");	//レスログ
		foreach($res_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		
		$fp = fopen(kako_dir."/res_{$article}.csv", "w");	//過去レスログ
		foreach($res_log_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		
		$fp = fopen(kako_dir."/topic_{$article}.csv", "w");	//過去トピックログ
		foreach($topic_log_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		
		$this -> article_number_up(1);
	}
	
	// 返信ログ書き込み
	function write_res($str)
	{
		//初期化
		global $post_data;
		$topic_data = array();
		$res_data   = array();
		$writ_data  = explode(",", $str);
		$take = 0;
		
		$fl = fopen(lock_file, "w") or die($this->html_error("ファイルが開けません"));
		flock($fl,LOCK_EX);
		/* トピックの処理 */
		$fp = fopen(topic_log, "r");
		while($buf = fgets($fp))
		{
			$data = explode(",", $buf);
			if($data[0] != $post_data["number"])
			{
				$topic_data[] = $buf;
			}else{
				if($this->res_count($data[12]) >= max_num)
				{
					fclose($fp);
					$this->html_error("記事が最大数".max_num."を超えています");
					return;
				}
				$take = 1;	// トピックを見つけた意図
				$data[11] = $post_data["name"];
				$data[12].= "{$writ_data[0]}<>";
				$data[13] = time();
				$watch_data = implode(",", $data);
			}
		}
		fclose($fp);
		if(!$take)
		{
			$this -> html_error("トピックが存在しません");
			return;
		}
		array_unshift($topic_data, $watch_data);	//記事をトップに移動
		$fp = fopen(topic_log, "w");
		foreach($topic_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		/* 記事の処理 */
		$take = 0;
		$fp = fopen(res_log, "r");
		while($buf = fgets($fp))
		{
			$match = preg_match("/^[0-9]+,".$post_data["number"].",/", $buf);
			if(!$take && $match)
			{
				$take = 1;	// 記事列を見つけた事を示す
			}
			if($take && !$match)	// 記事列の最後尾に追加
			{
				$res_data[] = $str;
				$str = NULL;
				$take = 0;
			}
			$res_data[] = $buf;
		}
		if($str && $take)	//記事列が見つからなかった場合
		{
			$res_data[] = $str;
			$str = NULL;
			$take = 0;
		}
		if($str)
		{
			array_unshift($res_data, $str);
		}
		fclose($fp);
		$fp = fopen(res_log, "w");
		foreach($res_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		fclose($fl);
	}
	
	// クッキーの作成
	function set_cookie()
	{
		global $post_data,$cookie_data;
		
		$post_data["url"]  = str_replace( "http://", "", $post_data["url"]);
		
		foreach($cookie_data as $key => $val)
		{
			if($post_data[$key])
			{
				setcookie($key ,$post_data[$key] ,time() + 60*60*24*30);
			}
		}
	}
	
	/************************************
	 *
	 *	記事編集
	 *
	 ************************************/
	function html_edit_key()
	{
		global $get_data,$cookie_data;
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=edit\" name=\"form\">\n";
		echo "<table class=\"form\">\n";
		echo "<tbody>\n";
		echo "<input type=\"hidden\" name=\"number\" value=\"{$get_data["no"]}\">\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:center;\">記事No:{$get_data["no"]}</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"text-align:center;\">パスワード入力&nbsp;\n";
		echo "<select name=\"action\">\n";
		echo "<option value=\"edit\" selected>編集</option>\n";
		echo "<option value=\"del\">削除</option>\n";
		echo "</select>\n";
		echo "<input type=\"password\" name=\"key\" value=\"{$cookie_data["key"]}\">\n";
		echo "<input type=\"submit\" value=\"送信\"></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	function edit_controll()
	{
		global $post_data;
		
		if($err_mge = $this->check_edit_form())
		{
			$this -> html_error($err_mge);
		}else{
			$edit_log = NULL;
			
			/* 編集する記事の検索 */
			$fp = fopen(topic_log, "r");
			while($str = fgets($fp))
			{
				$data = explode(",", $str);
				if($data[0] == $post_data["number"])
				{
					$edit_log = topic_log;
					break;
				}
			}
			fclose($fp);
			if(!$edit_log)	// topic_logで見つからければres_logを探す
			{
				$fp = fopen(res_log, "r");
				while($str = fgets($fp))
				{
					$data = explode(",", $str);
					if($data[0] == $post_data["number"])
					{
						$edit_log = res_log;
						break;
					}
				}
				fclose($fp);
			}
			
			// 記事が見つからなかったら
			if(!$edit_log)
			{
				$this -> html_error("指定された記事が存在ません");
				return;
			}
			if(crypt($post_data["key"], $data[8]) != $data[8] && $post_data["key"] != master_pass )
			{
				$this -> html_error("パスワードが違います");
				return;
			}
			
			/* 編集 */
			if($post_data["action"] == "edit")
			{
				$this -> encode($data);
				$this -> html_start();
				$this -> html_form_edit($data);
			}
			// 削除
			else if($post_data["action"] == "del")
			{
				$fl = fopen(lock_file, "w") or die($this->html_error("ファイルが開けません"));
				$fp = fopen($edit_log, "r");
				while($str = fgets($fp))
				{
					$data = explode(",", $str);
					if($data[0] != $post_data["number"])
					{
						$log_data[] = $str;
					}else{
						$data[7] = del_mge;
						$data[8] = NULL;
						$log_data[] = implode(",", $data);
					}
				}
				fclose($fp);
				$fp = fopen($edit_log, "w");
				foreach($log_data as $val)
				{
					fwrite($fp, $val);
				}
				fclose($fp);
				fclose($fl);
				header("Location:".bbs_dir.bbs_name);
			}
		}
	}
	function edit_write()
	{
		global $post_data;
		
		if($err_mge = $this->check_edit_form())
		{
			$this -> html_error($err_mge);
		}else{
			$edit_log = NULL;
			
			/* 編集する記事の検索 */
			$fp = fopen(topic_log, "r");
			while($str = fgets($fp))
			{
				$data = explode(",", $str);
				if($data[0] == $post_data["number"])
				{
					$edit_log = topic_log;
					break;
				}
			}
			fclose($fp);
			if(!$edit_log)
			{
				$fp = fopen(res_log, "r");
				while($str = fgets($fp))
				{
					$data = explode(",", $str);
					if($data[0] == $post_data["number"])
					{
						$edit_log = res_log;
						break;
					}
				}
				fclose($fp);
			}
			
			if(!$edit_log)
			{
				$this -> html_error("指定された記事が存在ません");
				return;
			}
			if(crypt($post_data["key"], $data[8]) != $data[8] && $post_data["key"] != master_pass )
			{
				$this -> html_error("パスワードが違います");
				return;
			}
			
			/* 編集 */
			if($post_data["action"] == "edit")
			{
				foreach($post_data as $key => $val){$post_data[$key] = $this->str_convert($val);}
				$this -> convert($post_data);
				$fl = fopen(lock_file, "w") or die($this->html_error("ファイルが開けません"));
				flock($fl,LOCK_EX);
				$fp = fopen($edit_log, "r");
				while($str = fgets($fp))
				{
					$data = explode(",", $str);
					if($data[0] != $post_data["number"])
					{
						$log_data[] = $str;
					}else{
						$data[3] = $post_data["title"];
						$data[4] = $post_data["name"];
						$data[5] = $post_data["mail"];
						$data[6] = $post_data["url"];
						$data[7] = $post_data["com"];
						$log_data[] = implode(",", $data);
					}
				}
				fclose($fp);
				$fp = fopen($edit_log, "w");
				foreach($log_data as $val)
				{
					fwrite($fp, $val);
				}
				fclose($fp);
				fclose($fl);
			}
			header("Location:".bbs_dir.bbs_name);
		}
	}
	
	function check_edit_form()
	{
		global $post_data;
		
		if(!$post_data["key"])
			return "パスワード未記入";
		else if(!preg_match("/^[0-9]+$/", $post_data["number"]))
			return "記事番号が不正です";
		else if($post_data["action"] != "del" && $post_data["action"] != "edit")
			return "アクションが不正です";
		else
			return NULL;
	}
	// エンコード
	function encode(&$data)
	{
		$data[7] = preg_replace("/<br>/i","\n", $data[7]);
		if(url_flag)
		{
			$data[7] = preg_replace( "/<a .+>(http:\/\/[\w\.\/\-=&%?;#]+)<\/a>/i", "$1", $data[7]);
		}
	}
	
	//編集用投稿フォーム
	function html_form_edit($data)
	{
		global $cookie_data,$post_data;
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=edit_write\" onsubmit=\"return submitForm()\">\n";
		echo "<table class=\"form\">\n";
		echo "<tbody>\n";
		echo "<input type=\"hidden\" name=\"number\" value=\"{$data[0]}\">\n";
		echo "<input type=\"hidden\" name=\"action\" value=\"{$post_data["action"]}\">\n";
		echo "<tr>\n";
		echo "<th>名前</th>\n";
		echo "<td><input type=\"text\" name=\"name\" size=\"50\" maxlength=\"".name_len."\" value=\"{$data[4]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>E-Mail</th>\n";
		echo "<td><input type=\"text\" name=\"mail\" size=\"50\" maxlength=\"".mail_len."\" value=\"{$data[6]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>URL</th>\n";
		echo "<td><input type=\"text\" name=\"url\" size=\"50\" maxlength=\"".url_len."\" value=\"http://{$data[5]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>タイトル</th>\n";
		echo "<td><input type=\"text\" name=\"title\" size=\"50\" maxlength=\"".title_len."\" value=\"{$data[3]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>コメント</th>\n";
		echo "<td><textarea id=\"com\" name=\"com\" cols=60 rows=12>{$data[7]}</textarea></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>認証キー</th>\n";
		echo "<td><input type=\"password\" name=\"key\" size=\"10\" value=\"{$cookie_data["key"]}\"> <input type=\"submit\" value=\"編集\"></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	/************************************
	 *
	 *	管理室
	 *
	 ************************************/
	function master_ini()
	{
		global $post_data;
		$post_name   = array("del","edit","lname");
		foreach($post_name as $key)
		{
			if(isset($_POST[$key]))
			{
				$post_data[$key] = $_POST[$key];
			}else{
				$post_data[$key] = NULL;
			}
		}
	}
	function master_controller()
	{

		if( !$this -> basic_auth() )
		{
			return;
		}else{
			global $post_data,$get_data;
			$this -> master_ini();
			
			$this -> html_start();
			
			echo "<h2>管理室</h2>\n";
			switch($get_data["type"])
			{
				case "del":
					$this -> master_del();
					return;
				case "edit":
					$this -> master_topic_edit();
					return;
				case "res":
					$this -> master_res();
					return;
			}
			switch($post_data["action"])
			{
				case "del":
					$this -> master_del();
					break;
				case "edit":
					$this -> master_edit();
					break;
				default:
					$this -> master_topic();
			}
		}
	}
	function master_topic()
	{
		global $ini_data,$get_data;
		$topic = array();
		$total = 0;
		$j     = 0;
		if(!$get_data["page"])
		{
			$point = 0;
		}else{
			$point = $get_data["page"] * $ini_data["topic_num"];
		}
		
		// ログファイル及びログ最大数の取得
		$topic = file($ini_data["topic_log"]);
		$total = count($topic);
		
		// トピックの表示
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=master\">\n";
		echo "<table class=\"style\">\n";
		for($i=$point; $i < $total; ++$i)
		{
			if($j++ >= $ini_data["topic_num"])
			{
				break;
			}
			if($i-$point%10 == 0)
			{
				$this -> master_topic_head();
			}
			$data = explode(",", $topic[$i]);
			$this -> master_topic_body($data);
		}
		echo "</table>\n";
		echo "<p>選択された記事を\n";
		echo "<select name=\"action\">\n";
		echo "<option value=\"edit\" selected>編集</option>\n";
		echo "<option value=\"del\">削除</option>\n";
		echo "</select>\n";
		echo " <input type=\"submit\" value=\"実行\"></p>\n";
		echo "</form>\n";
		// ページリンク
		if($total > $ini_data["topic_num"])
		{
			echo "<p>";
			if($get_data["page"])
			{
				$num = $get_data["page"]-1;
				echo "<a href=\"./".bbs_name."?mode=master&page={$num}\" title=\"前のページ\"><<</a>&nbsp;&nbsp;";
			}
			//ダイレクトリンク
			$j = $total / $ini_data["topic_num"];
			for($i=0; $i <= $j; ++$i)
			{
				if($i != $get_data["page"])
				{
					echo "<a href=\"./".bbs_name."?mode=master&page={$i}\" title=\"ダイレクトリンク\">[$i]</a>&nbsp;";
				}else{
					echo "[$i]&nbsp;";
				}
			}
			if($total-$ini_data["topic_num"] > $get_data["page"]*$ini_data["topic_num"])
			{
				$num = $get_data["page"]+1;
				echo "&nbsp;<a href=\"./".bbs_name."?mode=master&page={$num}\" title=\"次のページ\">>></a>";
			}
			echo "</p>\n";
		}
	}
	// HTML表記 テーブルヘッダー
	function master_topic_head()
	{
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<th>トピックタイトル</th>\n";
		echo "<th>トピック作成者</th>\n";
		echo "<th>作成日</th>\n";
		echo "<th>作成者IP</th>\n";
		echo "<th>作成者ホスト名</th>\n";
		echo "<th>最終投稿者</th>\n";
		echo "<th>最終更新</th>\n";
		echo "<th>記事数</th>\n";
		echo "<th>編集</th>\n";
		echo "<th>削除</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
	}
	
	// HTML表記 テーブルボディー
	function master_topic_body($data)
	{
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td>{$this->new_flag($data[13])}</td>\n";
		echo "<td style=\"text-align:left;\"><a href=\"./".bbs_name."?mode=master&type=res&no={$data[0]}\" style=\"font-size:140%;font-weight:bold;\" title=\"{$this->substr($data[7])}\">{$data[3]}</a></td>\n";
		echo "<td>$data[4]</td>\n";
		echo "<td nowrap align=\"center\">{$this->days($data[2])}</td>\n";
		echo "<td>{$data[10]}</td>\n";
		echo "<td>{$data[9]}</td>\n";
		echo "<td>$data[11]</td>\n";
		echo "<td nowrap align=\"center\">{$this->days($data[13])}</td>\n";
		echo "<td>{$this->res_count($data[12])}</td>\n";
		echo "<td><input type=\"radio\" name=\"edit\" value=\"{$data[0]}\"></td>\n";
		echo "<td><input type=\"checkbox\" name=\"del[$data[0]]\" value=\"1\"></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}
	function master_res()
	{
		// 初期値
		global $get_data;
		$view_data = array();
		$name_list = array();
		$take = 0;
		
		// 番号の正当性をチェック
		if(!preg_match("/^[0-9]+$/", $get_data["no"]))
		{
			$this -> html_error("番号が不明です");
			return;
		}
		
		// 指定のトピックを読み込む
		$fp = fopen(topic_log, "r");
		while($str = fgets($fp))
		{
			$data = explode(",", $str);
			if($data[0] == $get_data["no"])
			{
				$view_data[] = $data;
				$name_list[] = "<a href=\"#{$data[0]}\" title=\"記事No:{$data[0]}\">[{$data[0]}]&nbsp;{$data[4]}</a><br>\n";
				break;
			}
		}
		fclose($fp);
		
		// トピックの記事を読み込む
		$fp = fopen(res_log, "r");
		while($str = fgets($fp))
		{
			$data = explode(",", $str);
			if($data[1] == $get_data["no"] || $data[0] == $get_data["no"] )
			{
				if(!$take)
				{
					$take = 1;	// 記事列を見つけた事を示す
				}
				
				$view_data[] = $data;
				$name_list[] = "・<a href=\"#{$data[0]}\" title=\"記事No:{$data[0]}\">[{$data[0]}]&nbsp;{$data[4]}</a><br>\n";
			}
			// 早々に閉じる
			else if($take)
			{
				break;
			}
		}
		fclose($fp);
		
		// 記事が見つからなかったらエラーを出し終了
		if(!$view_data)
		{
			$this -> html_error("記事がみつかりませんでした");
			return;
		}
		
		/* 記事の表示 */
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=master&type=del\">\n";
		echo "<table class=\"topic\">\n";
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th colspan=\"2\">{$view_data[0][3]}</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td class=\"td1\">\n";
		//ダイレクトリンク
		foreach($name_list as $val)
		{
			echo $val;
		}
		echo "</td>\n";
		echo "<td class=\"td2\">\n";
		echo "<table class=\"style\" style=\"width:100%;\">\n";
		// トピック
		$this -> html_master_res($view_data[0],0);
		echo "</table>\n";
		echo "<img src=\"./img/line.png\" border=\"0\">\n";
		echo "<table class=\"style\" style=\"width:100%;\">\n";
		// トピックの記事
		$total = count($view_data);
		for($i=1; $i < $total; ++$i)
		{
			$this -> html_master_res($view_data[$i],$i);
		}
		echo "</table>\n";
		
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "<p><input type=\"submit\" value=\"選択された記事を削除する\"></p>\n";
		echo "</form>\n";
	}
	
	// 記事HTMLスタイル
	function html_master_res($data,$num=0)
	{
		$this -> sub_object($data);
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:left;border-right-width:0pt;\"><a name=\"{$data[0]}\"></a>No:{$data[0]}&nbsp;&nbsp;Name:&nbsp;<span style=\"font-size:110%;\">{$data[4]}</span>{$data[5]}</th>\n";
		echo "<th style=\"text-align:right;border-left-width:0pt;\">\n";
		echo "[<a href=\"./".bbs_name."?mode=key&no={$data[0]}\">編集</a>]\n";
		echo "<input type=\"checkbox\" name=\"del[$data[0]]\" value=\"{$data[0]}\"></th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td colspan=\"2\" style=\"text-align:left;";
		if($num)
		{
			echo "border-width:0pt;";
		}
		echo "\">\n";
		echo "IP:&nbsp;<u>{$data[10]}</u>&nbsp;&nbsp;ホスト名:&nbsp;<u>{$data[9]}</u>\n";
		echo "<h2>Title:&nbsp;{$data[3]}</h2>\n";
		echo "<p id=\"{$num}\">\n";
		echo "{$data[7]}";
		echo "</p>\n";
		echo "<div style=\"text-align:right;\">\n";
		echo "{$this->days($data[2])}";
		echo "</div>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}
	
	// Basic認証
	function basic_auth()
	{
		$pass = parse_ini_file(pass_file);
		if( empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] == "" )
		{
  			header("WWW-Authenticate: Basic realm=\"admin\"");
  			header("HTTP/1.0 401 Unauthorized");
			$this -> html_error("キャンセルされました");
			return 0;	//失敗
		}else{
			if( isset($pass[$_SERVER['PHP_AUTH_USER']]) && $pass[$_SERVER['PHP_AUTH_USER']] == $_SERVER['PHP_AUTH_PW'])
			{
				return 1;	//成功
			}else{
	  			header("WWW-Authenticate: Basic realm=\"admin\"");
	  			header("HTTP/1.0 401 Unauthorized");
				$this -> html_error("ユーザーIDまたはパスワードが違います");
				return 0;	//失敗
			}
		}
	}
	// 完全削除
	function master_del()
	{
		//初期化
		global $post_data;
		$topic_data = array();
		$res_data   = array();
		$number     = array();
		
		if( !isset($post_data["del"]) )
		{
			$this -> html_error("記事が選択されていません");
			return;
		}
		
		$fl = fopen(lock_file, "w") or die($this->html_error("ファイルが開けません"));
		flock($fl,LOCK_EX);
		/* トピックの処理 */
		$fp = fopen(topic_log, "r");
		while($buf = fgets($fp))
		{
			$data = explode(",", $buf);
			if( !isset($post_data["del"][$data[0]]) )
			{
				$topic_data[] = $buf;
			}else{
				$number["$data[0]"] = 1;
				print "トピック{$data[0]}を削除しました<br>";
			}
		}
		fclose($fp);
		$fp = fopen(topic_log, "w");
		foreach($topic_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		/* 記事の処理 */
		$fp = fopen(res_log, "r");
		while($buf = fgets($fp))
		{
			$data = explode(",", $buf);
			if( !isset($post_data["del"][$data[0]]) && !isset($number["$data[1]"]) )
			{
				$res_data[] = $buf;
			}else{
				print "・{$data[0]}を削除しました<br>";
			}
		}
		fclose($fp);
		$fp = fopen(res_log, "w");
		foreach($res_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		fclose($fl);
		header("Location:".bbs_dir.bbs_name."?mode=master");
	}
	// トピック情報編集
	function master_edit()
	{
		//初期化
		global $post_data;
		$error = NULL;
		
		if( !isset($post_data["edit"]) )
		{
			$this -> html_error("記事が選択されていません");
			return;
		}
		
		/* トピックの読み込み */
		$fp = fopen(topic_log, "r");
		while($buf = fgets($fp))
		{
			$data = explode(",", $buf);
			if( $data[0] == $post_data["edit"] )
			{
				$error = 1;
				break;
			}
		}
		fclose($fp);
		if( !$error )
		{
			$this -> html_error("トピックがみつかりませんでした");
			return;
		}
		$this -> encode($data);
		$this -> html_master_edit($data);

	}
	function html_master_edit($data)
	{
		global $cookie_data,$post_data;
		echo "<form method=\"POST\" action=\"./".bbs_name."?mode=master&type=edit\" onsubmit=\"return submitForm()\">\n";
		echo "<table class=\"form\">\n";
		echo "<tbody>\n";
		echo "<input type=\"hidden\" name=\"number\" value=\"{$data[0]}\">\n";
		echo "<tr>\n";
		echo "<th>名前</th>\n";
		echo "<td><input type=\"text\" name=\"name\" size=\"50\" maxlength=\"".name_len."\" value=\"{$data[4]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>E-Mail</th>\n";
		echo "<td><input type=\"text\" name=\"mail\" size=\"50\" maxlength=\"".mail_len."\" value=\"{$data[6]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>URL</th>\n";
		echo "<td><input type=\"text\" name=\"url\" size=\"50\" maxlength=\"".url_len."\" value=\"http://{$data[5]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>タイトル</th>\n";
		echo "<td><input type=\"text\" name=\"title\" size=\"50\" maxlength=\"".title_len."\" value=\"{$data[3]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>コメント</th>\n";
		echo "<td><textarea id=\"com\" name=\"com\" cols=60 rows=12>{$data[7]}</textarea></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>最終投稿者</th>\n";
		echo "<td><input type=\"text\" name=\"lname\" size=\"50\" maxlength=\"".name_len."\" value=\"{$data[11]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th>記事数（{$this->res_count($data[12])}）</th>\n";
		echo "<td><input type=\"text\" name=\"num\" size=\"50\" value=\"{$data[12]}\"></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th></th>\n";
		echo "<td><input type=\"submit\" value=\"編集\"></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	function master_topic_edit()
	{
		//初期化
		global $post_data;
		$topic_data = array();
		
		foreach($post_data as $key => $val){$post_data[$key] = $this->str_convert($val);}
		$this -> convert($post_data);
		
		/* トピックの読み込み */
		$fl = fopen(lock_file, "w") or die($this->html_error("ファイルが開けません"));
		flock($fl,LOCK_EX);
		$fp = fopen(topic_log, "r");
		while($buf = fgets($fp))
		{
			$data = explode(",", $buf);
			if( $data[0] != $post_data["number"] )
			{
				$topic_data[] = $buf;
			}else{
				$data[3] = $post_data["title"];
				$data[4] = $post_data["name"];
				$data[5] = $post_data["mail"];
				$data[6] = $post_data["url"];
				$data[7] = $post_data["com"];
				$data[11]= $post_data["lname"];
				$data[12]= $_POST["num"];
				$topic_data[] = implode(",", $data);
			}
		}
		fclose($fp);
		$fp = fopen(topic_log, "w");
		foreach($topic_data as $val)
		{
			fwrite($fp, $val);
		}
		fclose($fp);
		fclose($fl);
	}
	
	/************************************
	 *
	 *	検索
	 *
	 ************************************/
	function search_controller()
	{
		global $get_data;
		$this -> html_start();
		$this -> search_form($get_data);
		if($get_data["srch"] && isset($get_data["type"]))
		{
			$num  = 0;
			$name = NULL;
			$search_data = array();
			
			$search = $this -> str_convert($get_data["srch"]);
			$search = str_replace("\\", "\\\\", $search);
			$search = trim($search, " 　");
			$search = preg_replace("/[ |　]+/", "|", $search);
			
			foreach($get_data["type"] as $val)
			{
				switch($val)
				{
					case"title":
						$name.="[タイトル]";
						break;
					case"name":
						$name.="[名前]";
						break;
					case"com":
						$name.="[本文]";
						break;
				}
			}
			$fp = fopen(topic_log, "r");
			while($buf = fgets($fp))
			{
				$data = explode(",", $buf);
				$num += $this -> search_log($get_data, $data, $search_data, $search);
			}
			fclose($fp);
			$fp = fopen(res_log, "r");
			while($buf = fgets($fp))
			{
				$data = explode(",", $buf);
				$num += $this -> search_log($get_data, $data, $search_data, $search);
			}
			fclose($fp);
			echo "<table class=\"form\">\n";
			echo "<thead>\n";
			echo "<tr>\n";
			echo "<th style=\"text-align:center;\">\n";
			echo "{$name}からの検索結果\"{$get_data["srch"]}\"は{$num}件ヒットしました";
			echo "</th>\n";
			echo "</tr>\n";
			echo "</thead>\n";
			echo "<table class=\"style\">\n";
			foreach($search_data as $val)
			{
				$this -> html_search($val);
			}
			echo "</table>\n";
		}
	}
	
	function search_log($get_data, $data, &$search_data, $search)
	{
		foreach($get_data["type"] as $val)
		{
			switch($val)
			{
				case"title":
					// if($data[3] = preg_replace("(".$search.")", "<span style=\"background-color:#ffff00\">\\1</span>", $data[3]))
					if(ereg($search, $data[3]))
					{
						$search_data[] = $data;
						return 1;
					}
					break;
				case"name":
					if(ereg($search, $data[4]))
					{
						$search_data[] = $data;
						return 1;
					}
					break;
				case"com":
					if(ereg($search, $data[7]))
					{
						$search_data[] = $data;
						return 1;
					}
					break;
			}
		}
		return 0;
	}
	
	function search_form($get_data){
		echo "<form method=\"GET\" action=\"./".bbs_name."\" name=\"form\">\n";
		echo "<table class=\"form\">\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:center;\">ワード検索</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td style=\"text-align:center;\">\n";
		echo "<input type=\"hidden\" name=\"mode\" value=\"search\">";
		echo "<input type=\"text\" size=\"30\" name=\"srch\" value=\"{$get_data["srch"]}\">&nbsp;";
		echo "<input type=\"submit\" value=\"検索\"><br>\n";
		echo "<small>本文</small><input type=\"checkbox\" name=\"type[]\" value=\"com\">\n";
		echo "<small>タイトル</small><input type=\"checkbox\" name=\"type[]\" value=\"title\">\n";
		echo "<small>名前</small><input type=\"checkbox\" name=\"type[]\" value=\"name\">\n";
		if($get_data["srch"] && !isset($get_data["type"]))
		echo "<small style=\"color:red\">最低１つはチェックして下さい</small>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
	
	function html_search($data)
	{
		$this -> sub_object($data);
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:left;border-right-width:0pt;\"><a name=\"{$data[0]}\"></a>No:{$data[0]}&nbsp;&nbsp;Name:&nbsp;<span style=\"font-size:110%;\">{$data[4]}</span>{$data[5]}</th>\n";
		echo "<th style=\"text-align:right;border-left-width:0pt;\">\n";
		echo "[<a href=\"./".bbs_name."?mode=res&no=";
		if($data[1]){echo $data[1];}else{echo $data[0];}
		echo "\">トピックの表示</a>]</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td colspan=\"2\" style=\"text-align:left;\">\n";
		echo "<h2>Title:&nbsp;{$data[3]}</h2>\n";
		echo "<p>\n";
		echo "{$data[7]}";
		echo "</p>\n";
		echo "<div style=\"text-align:right;\">\n";
		echo "{$this->days($data[2])}";
		echo "</div>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}
	
	/************************************
	 *
	 *	ヘルプ
	 *
	 ************************************
	function html_help()
	{
		echo "<table class=\"style\">\n";
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th style=\"text-align:left;\">掲示板の使い方</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td style=\"text-align:left;\">\n";
		echo "<ul>メニュー\n";
		echo "<li>新規投稿→トピックを作成することができます";
		echo "<li>トピック表示→トップページを表示することができます";
		echo "<li>検索→現行ログから文字列を検索することができます";
		echo "<li>ホーム→掲示板を運営するサイトに飛ぶことができます";
		echo "</ul>\n";
		echo "<ul>特徴\n";
		echo "<li>ありません";
		echo "</ul>\n";
		echo "<ul>仕様\n";
		echo "<li>ごく普通な掲示板です";
		echo "</ul>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
	}*/
	
	/************************************
	 *
	 *	過去ログ
	 *
	 ************************************/
	function kako_controller()
	{
		global $get_data;
		$num     = $this -> article_number_get(1);
		$err_mge = $this -> kako_error($num,$get_data);
		if($err_mge)
		{
			$this -> html_error($err_mge);
			return;
		}

		switch($get_data["type"])
		{
			case"topic":
				$this -> kako_topic_view($get_data);
				break;
			case"res":
				$this -> kako_res_view($get_data);
				break;
			default:
				$this -> kako_view($num);
		}

	}
	function kako_error($num,$get_data)
	{
		if(!preg_match("/^[0-9]*$/", $get_data["no"]))
			return "番号が不明です";
		else if($num <= $get_data["no"])
			return "指定の過去ログは存在しません";
		else if($num <= 1)
			return "過去ログは存在しません";
		else if($get_data["no"] <= 0 && $get_data["type"])
			return "過去ログ「0」は存在しません";
		else
			return null;
	}
	function kako_view($num)
	{
		$this -> html_start();
		echo "<ul>\n";
		for($i=1; $i < $num; ++$i)
		{
			echo "<li><a href=\"index.php?mode=kako&type=topic&no={$i}\">過去ログ{$i}</a>\n";
		}
		echo "</ul>\n";
	}
	function kako_topic_view($get_data)
	{
		// 初期値
		global $ini_data;
		$topic = array();
		$total = 0;
		$j     = 0;
		// チェック
		if(!preg_match("/^[0-9]*$/", $get_data["page"]))
		{
			$this -> html_error("番号が不明です");
			return;
		}
		// ページの取得
		if(!$get_data["page"])
		{
			$point = 0;
		}else{
			$point = $get_data["page"] * $ini_data["topic_num"];
		}
		
		// ログファイル及びログ最大数の取得
		$topic = file("{$ini_data["kako_dir"]}/topic_{$get_data["no"]}.csv");
		$total = count($topic);
		
		// トピックの表示
		$this -> html_start();
		echo "<table class=\"style\">\n";
		for($i=$point; $i < $total; ++$i)
		{
			if($j++ >= $ini_data["topic_num"])
			{
				break;
			}
			if(($i-$point)%10 == 0)
			{
				$this -> html_topic_head();
			}
			$data = explode(",", $topic[$i]);
			$this -> kako_topic_body($data,$get_data);
		}
		echo "</table>\n";
		// ページリンク
		if($total > $ini_data["topic_num"])
		{
			echo "<p>";
			if($get_data["page"])
			{
				$num = $get_data["page"]-1;
				echo "<a href=\"./".bbs_name."?mode=kako&type=topic&no={$get_data["no"]}&page={$num}\" title=\"前のページ\"><<</a>&nbsp;&nbsp;";
			}
			//ダイレクトリンク
			$j = $total / $ini_data["topic_num"];
			for($i=0; $i <= $j; ++$i)
			{
				if($i != $get_data["page"])
				{
					echo "<a href=\"./".bbs_name."?mode=kako&type=topic&no={$get_data["no"]}&page={$i}\" title=\"ダイレクトリンク\">[$i]</a>&nbsp;";
				}else{
					echo "[$i]&nbsp;";
				}
			}
			if($total-$ini_data["topic_num"] > $get_data["page"]*$ini_data["topic_num"])
			{
				$num = $get_data["page"]+1;
				echo "&nbsp;<a href=\"./".bbs_name."?mode=kako&type=topic&no={$get_data["no"]}&page={$num}\" title=\"次のページ\">>></a>";
			}
			echo "</p>\n";
		}
	}
	function kako_res_view($get_data)
	{
		// 初期値
		$view_data = array();
		$name_list = array();
		$take = 0;
		$take = 0;
		$quote = null;
		
		// 番号の正当性をチェック
		if(!preg_match("/^[0-9]+$/", $get_data["page"]))
		{
			$this -> html_error("番号が不明です");
			return;
		}
		
		// 指定のトピックを読み込む
		$fp = fopen(kako_dir."/topic_{$get_data["no"]}.csv", "r");
		while($str = fgets($fp))
		{
			$data = explode(",", $str);
			if($data[0] == $get_data["page"])
			{
				$view_data[] = $data;
				$name_list[] = "<a href=\"#{$data[0]}\" title=\"記事No:{$data[0]}\">[{$data[0]}]&nbsp;{$data[4]}</a><br>\n";
				if($data[0] == $get_data["quote"])
				{
					$data[7] = preg_replace("/<br>/i","\n> ", $data[7]);
					$quote = "> ".$data[7];
				}
				break;
			}
		}
		fclose($fp);
		
		// トピックの記事を読み込む
		$fp = fopen(kako_dir."/res_{$get_data["no"]}.csv", "r");
		while($str = fgets($fp))
		{
			$data = explode(",", $str);
			if($data[1] == $get_data["page"] || $data[0] == $get_data["page"] )
			{
				if(!$take)
				{
					$take = 1;	// 記事列を見つけた事を示す
				}
				if($data[0] == $get_data["quote"])
				{
					$data[7] = preg_replace("/<br>/i","\n> ", $data[7]);
					$quote = "> ".$data[7];
				}
				
				$view_data[] = $data;
				$name_list[] = "・<a href=\"#{$data[0]}\" title=\"記事No:{$data[0]}\">[{$data[0]}]&nbsp;{$data[4]}</a><br>\n";
			}
			// 早々に閉じる
			else if($take)
			{
				break;
			}
		}
		fclose($fp);
		
		// 記事が見つからなかったらエラーを出し終了
		if(!$view_data)
		{
			$this -> html_error("記事がみつかりませんでした");
			return;
		}
		
		/* 記事の表示 */
		$this -> html_start();
		echo "<table class=\"topic\">\n";
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th colspan=\"2\">{$view_data[0][3]}</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td class=\"td1\">\n";
		//記事リンク
		foreach($name_list as $val)
		{
			echo $val;
		}
		echo "</td>\n";
		echo "<td class=\"td2\">\n";
		echo "<table class=\"style\" style=\"width:100%;\">\n";
		// トピック
		$this -> html_topic($view_data[0]);
		echo "</table>\n";
		echo "<img src=\"./img/line.png\" border=\"0\">\n";
		echo "<table class=\"style\" style=\"width:100%;\">\n";
		// トピックの記事
		$total = count($view_data);
		for($i=1; $i < $total; ++$i)
		{
			$this -> html_res($view_data[$i]);
		}
		echo "</table>\n";
		
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
	}
	
	// HTML表記 テーブルボディー
	function kako_topic_body($data,$get_data)
	{
		echo "<tbody>\n";
		echo "<tr>\n";
		echo "<td></td>\n";
		echo "<td style=\"text-align:left;\"><a href=\"./".bbs_name."?mode=kako&type=res&no={$get_data["no"]}&page={$data[0]}\" style=\"font-size:140%;font-weight:bold;\">{$data[3]}</a><div>{$this->substr($data[7])}</div></td>\n";
		echo "<td nowrap align=\"center\">{$this->days($data[13])}</td>\n";
		echo "<td>{$this->res_count($data[12])}</td>\n";
		echo "<td>$data[4]</td>\n";
		echo "<td>{$data[11]}</td>\n";
		echo "<td></td>\n";
		echo "</tr>\n";
		echo "</tbody>\n";
	}

	/************************************
	 *
	 *	HTML
	 *
	 ************************************/
	function html_start()
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
		echo "<html lang=\"ja\">\n";
		echo "<head>\n";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
		echo "<title>".bbs_title."</title>\n";
		echo "<style type=\"text/css\">\n";
		echo "<!--\n";
		echo "body,table{\n";
		echo "	font-size : 11pt;\n";
		echo "}\n";
		echo "body{\n";
		echo "	background-image          : url(\"".back_img."\");\n";
		echo "	scrollbar-face-color      :".back_color.";\n";
		echo "	scrollbar-track-color     :".back_color.";\n";
		echo "	scrollbar-3dlight-color   :".back_color.";\n";
		echo "	scrollbar-darkshadow-color:".back_color.";\n";
		echo "	scrollbar-arrow-color     :".table_color2.";\n";
		echo "	scrollbar-shadow-color    :".table_color2.";\n";
		echo "	scrollbar-highlight-color :".table_color2.";\n";
		echo "}\n";
		echo "a\n";
		echo "{\n";
		echo "	color : ".a_visited.";text-decoration:none;\n";
		echo "}\n";
		echo "a:link\n";
		echo "{\n";
		echo "	color : ".a_link.";\n";
		echo "}\n";
		echo "a:visited\n";
		echo "{\n";
		echo "	color : ".a_visited.";\n";
		echo "}\n";
		echo "a:hover\n";
		echo "{\n";
		echo "	color : ".a_hover.";text-decoration:underline;\n";
		echo "}\n";
		echo "h1{\n";
		echo "	width               : 90%;\n";
		echo "	font-size           : 26pt;\n";
		echo "	border-bottom-width : 1pt;\n";
		echo "	border-bottom-style : solid;\n";
		echo "	border-bottom-color : ".table_color2.";\n";
		echo "	text-align          : left;\n";
		echo "}\n";
		echo "h2{\n";
		echo "	width     : 100%;\n";
		echo "	font-size : 11pt;\n";
		echo "}\n";
		echo "p{\n";
		echo "	width : 90%;\n";
		echo "}\n";
		echo "table.style{\n";
		echo "	width            : 90%;\n";
		echo "	font-size        : 80%;\n";
		echo "	background-color : ".table_color3.";\n";
		echo "	border-collapse  : collapse;\n";
		echo "}\n";
		echo "table.style th{\n";
		echo "	border-style     : solid;\n";
		echo "	border-width     : 1pt;\n";
		echo "	border-color     : ".table_color2.";\n";
		echo "	color            : ".font_color2.";\n";
		echo "	background-color : ".table_color1.";\n";
		echo "	text-align       : center;\n";
		echo "	padding          : 5px 10px;\n";
		echo "	white-space      : nowrap\n";
		echo "}\n";
		echo "table.style td{\n";
		echo "	border       : solid;\n";
		echo "	border-width : 1pt;\n";
		echo "	border-color : ".table_color2.";\n";
		echo "	text-align   : center;\n";
		echo "	padding      : 5px 10px;\n";
		echo "}\n";
		echo "table.style div{\n";
		echo "	text-indent : 1em;\n";
		echo "}\n";
		echo "table.style p{\n";
		echo "	font-size : 11pt;\n";
		echo "	padding   : 0 10px;\n";
		echo "	width     : 100%;\n";
		echo "}\n";
		echo "table.topic{\n";
		echo "	width:90%;\n";
		echo "	font-size        : 100%;\n";
		echo "	background-color : ".table_color3.";\n";
		echo "	border-collapse  : collapse;\n";
		echo "}\n";
		echo "table.topic th{\n";
		echo "	font-size        : 115%;\n";
		echo "	border-style     : solid;\n";
		echo "	border-width     : 1pt;\n";
		echo "	border-color     : ".table_color2.";\n";
		echo "	color            : ".font_color2.";\n";
		echo "	background-color : ".table_color1.";\n";
		echo "	text-align       : left;\n";
		echo "	padding          : 5px 10px;\n";
		echo "	white-space      : nowrap\n";
		echo "}\n";
		echo "table.topic td.td1{\n";
		echo "	font-size          : 80%;\n";
		echo "	border             : solid;\n";
		echo "	border-width       : 1pt;\n";
		echo "	border-right-width : 0pt;\n";
		echo "	border-color       : ".table_color2.";\n";
		echo "	text-align         : left;\n";
		echo "	vertical-align     : top;\n";
		echo "	padding            : 15px 10px;\n";
		echo "	white-space        : nowrap\n";
		echo "}\n";
		echo "table.topic td.td2{\n";
		echo "	font-size         : 100%;\n";
		echo "	border            : solid;\n";
		echo "	border-width      : 1pt;\n";
		echo "	border-left-width : 0pt;\n";
		echo "	border-color      : ".table_color2.";\n";
		echo "	text-align        : center;\n";
		echo "	vertical-align    : top;\n";
		echo "	padding           : 15px 10px;\n";
		echo "}\n";
		echo "table.form{\n";
		echo "	width            : 90%;\n";
		echo "	font-size        : 80%;\n";
		echo "	background-color : ".back_color.";\n";
		echo "	border-collapse  : collapse;\n";
		echo "	border-style     : solid;\n";
		echo "	border-width     : 1pt;\n";
		echo "	border-color     : ".table_color1.";\n";
		echo "}\n";
		echo "table.form th{\n";
		echo "	background-color : ".table_color1.";\n";
		echo "	text-align       : right;\n";
		echo "	padding          : 5px 5px;\n";
		echo "	white-space      : nowrap;\n";
		echo "}\n";
		echo "table.form td{\n";
		echo "	padding : 5px 5px;\n";
		echo "}\n";
		echo "textarea {\n";
		echo "	width:600;\n";
		echo "	border : 1px solid ".table_color1.";\n";
		echo "}\n";
		echo "input {\n";
		echo "	border : 1px solid ".table_color1.";\n";
		echo "}\n";
		echo "// -->\n";
		echo "</style>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "<!--\n";
		echo "error_flag = 0;\n";
		echo "function submitForm()\n";
		echo "{\n";
		echo "	error_flag = 1;\n";
		echo "	return true;\n";
		echo "}\n";
		echo "window.onbeforeunload = function()\n";
		echo "{\n";
		echo "	if(document.getElementById(\"com\").value != \"\" && error_flag != 1)\n";
		echo "	{\n";
		echo "		echo \"return \"コメントに入力情報があります。\\nページを移動すると入力情報は失われてしまいますがよろしいでしょうか？\";\n";
		echo "	}\n";
		echo "}\n";
		echo "// -->\n";
		echo "</script>\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<center>\n";
		echo "<br><br><h1>";
		echo  bbs_title_img?bbs_title_img:bbs_title;
		echo "</h1>\n";
		echo "<p style=\"text-align:left;\">".bbs_message."</p>\n";
		echo "<div style=\"text-align:right;width:90%;white-space:nowrap;\"><img src='./img/write.gif' border='0'>&nbsp;<a href=\"".bbs_name."?mode=writing\">新規投稿</a>&nbsp;&nbsp;<img src='./img/topic.gif' border='0'>&nbsp;<a href=\"".bbs_name."\">トピック表示</a>&nbsp;&nbsp;<img src='./img/search.gif' border='0'>&nbsp;<a href=\"./index.php?mode=search\">検索</a>&nbsp;&nbsp;<img src='./img/topic.gif' border='0'>&nbsp;<a href=\"".bbs_name."?mode=kako\">過去ログ</a>&nbsp;&nbsp;<img src='./img/home.gif' border='0'>&nbsp;<a href=\"http://example.com/\">ホーム</a></div>\n";
	}
}
?>