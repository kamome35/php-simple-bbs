<?php
define("script_ver", "1.00");
require_once('controller.php');

$controller = new controller();

switch($get_data["mode"])
{
	case"res":
		$controller -> res_view();
		break;
	case"writing":
		$controller -> html_start();
		$controller -> html_form();
		break;
	case"key":
		$controller -> html_start();
		$controller -> html_edit_key();
		break;
	case"write":
		$controller -> write_controll();
		break;
	case"edit":
		$controller -> edit_controll();
		break;
	case"edit_write":
		$controller -> edit_write();
		break;
	case"kako":
		$controller -> kako_controller();
		break;
	case"master":
		$controller -> master_controller();
		break;
	case"search":
		$controller -> search_controller();
		break;
	default:
		$controller -> topic_view();
}
?>
<br><div style="text-align:right;width:90%;white-space:nowrap;"><a href="<?=bbs_name?>?mode=master" title="アクセス履歴は保存されます">管理室</a>&nbsp;&nbsp;&nbsp;<a href="https://github.com/kyoronet/php-simple-bbs" target="_blank" title="ver.<?=script_ver?>">EasyBBS</a></div>
</center>
</body>
</html>