<?php
/**
 * 多说插件
 *
 * @version        $Id: duoshuo.php 0 10:17 2012-4-27 xiaowu $
 * @package        DedeCMS.DUOSHUO
 * @copyright      Copyright (c) 2012 - , Duoshuo, Inc.
 * @link           http://www.duoshuo.com
 */
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC."/json.class.php");
$ds_DOMAIN = 'duoshuo.com';
$ds_STATIC_DOMAIN = 'static.duoshuo.com';
//保存多说设置
function SaveConfig($short_name,$secret){
	//保存Cache信息
	$cacheFile = DEDEDATA.'/duoshuo.inc.php';
	$cacheStr = <<<EOT
<?php if(!defined('DEDEINC')) exit("Request Error!");
global \$cfg_duoshuo;
\$cfg_duoshuo = array();
\$cfg_duoshuo['short_name'] = '$short_name';
\$cfg_duoshuo['secret'] = '$secret';
\$cfg_duoshuo['tag']='{dede:duoshuo/}';
?>
EOT;
	file_put_contents($cacheFile, $cacheStr);
}


//处理返回结果 多说设置
if(!empty($_GET) && isset($_GET['short_name']) && isset($_GET['secret'])){
	SaveConfig($_GET['short_name'],$_GET['secret']);
}
// 引入多说配置文件
include_once DEDEDATA.'/duoshuo.inc.php';
$adminid = $cuserLogin->getUserID();

$cur_url = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];

function ReplaceCommentTag(){
	global $cfg_duoshuo;
	$ajax_comment_file = DEDETEMPLATE.'/default/ajaxfeedback.htm';
	$ajax_comment_file_bak = DEDETEMPLATE.'/default/ajaxfeedback.bak.htm';

	if(file_exists($ajax_comment_file)){

		if(!file_exists($ajax_comment_file_bak)){
			$ret = copy($ajax_comment_file, $ajax_comment_file_bak);
			if(!$ret){
				$ret = '备份'.$ajax_comment_file.'失败，请检查该目录权限，或手动复制到'.$ajax_comment_file_bak;
				return ret;
			}
		}
		file_put_contents($ajax_comment_file, $cfg_duoshuo['tag']);
		return ret;
	}
}

//处理返回结果 多说
if(!empty($_GET) && isset($_GET['replace'])){
	ReplaceCommentTag();
}

/**
 *  打包选项信息
 *  例如：pageckageOptions();
 *
 * @access    public
 * @return    array
 */
function packageOptions()
{
	global $cfg_webname,$cfg_description,$cfg_basehost,$cfg_indexurl,$cfg_adminemail,$cur_url;
	$params = array(
			'name'			=>	htmlspecialchars_decode($cfg_webname),
			'description'	=>	htmlspecialchars_decode($cfg_description),
			'system'		=>	'dedecms',
			'callback'	=>	$cur_url,
			'url'		=>	$cfg_basehost.$cfg_indexurl,
			'siteurl'	=>	$cfg_basehost,
			'admin_email'	=>	$cfg_adminemail,
			//'timezone'	=> $cfg_cli_time,
	);	
	return $params;
}

if (!isset($cfg_duoshuo['short_name']) || !isset($cfg_duoshuo['secret']))
{
	$params = packageOptions();
	if(isset($cfg_duoshuo['short_name'])){
		$params['short_name'] = $cfg_duoshuo['short_name'];
	}
	$url = 'http://' . $ds_DOMAIN . '/connect-site/?'. http_build_query($params, null, '&');
	header("Location:" . $url, true);
	exit;
} else {
	$ajax_comment_file = DEDETEMPLATE.'/default/ajaxfeedback.htm';
	$tag_replaced =  false;
	if(file_exists($ajax_comment_file)){
		$comment_content = file_get_contents($ajax_comment_file);
		
		if(strpos($comment_content,$cfg_duoshuo['tag'])!==false){
			$tag_replaced = true;
		}
	}
	include DEDEADMIN.'/templets/duoshuo_manage.htm';
}