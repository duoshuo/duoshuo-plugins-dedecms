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
@ini_set('display_errors', 1);
require_once(DEDEINC."/json.class.php");
require_once(DEDEINC.'/arc.archives.class.php');

require_once(DEDEROOT.'/plus/duoshuo.php');
require_once(DEDEROOT.'/plus/duoshuo/Admin.php');

$duoshuoPlugin = Duoshuo_Dedecms::getInstance();
// 设置默认参数
$duoshuoPlugin->checkDefaultSettings();

//兼容0.1.x版本插件 引入多说配置文件 {{ 只用于 0.2.x
$configFile =  DEDEDATA.'/duoshuo.inc.php';

if(file_exists($configFile)){
	if($duoshuoPlugin->getOption('short_name') == '' ||$duoshuoPlugin->getOption('secret') == ''){//如果数据库设置不完整,从文件导入
		include_once $configFile;
		global $cfg_duoshuo;
		if(isset($cfg_duoshuo))
		{
			if($duoshuoPlugin->getOption('short_name') == '' && !empty($cfg_duoshuo['short_name'])){
				$duoshuoPlugin->updateOption('short_name', $cfg_duoshuo['short_name']);
			}
			if($duoshuoPlugin->getOption('secret') == ''  && !empty($cfg_duoshuo['secret'])){
				$duoshuoPlugin->updateOption('secret',$cfg_duoshuo['secret']);
			}
		}
	}

	if($duoshuoPlugin->getOption('short_name') != '' && $duoshuoPlugin->getOption('secret') != ''){
		$success = unlink($configFile);//如果数据库已经设置，删除文件
	}
}
//}}

//从服务器返回的注册结果
if(!empty($_GET) && isset($_GET['duoshuo_connect_site']) && isset($_GET['short_name']) && isset($_GET['secret'])){
	$keys = array('short_name','secret');
	foreach ($keys as $key){
		$duoshuoPlugin->updateOption($key,$_GET[$key]);
	}
	$action = 'installStep1';
}

if($duoshuoPlugin->getOption('short_name') == '' || $duoshuoPlugin->getOption('secret') == ''){
	$params = $duoshuoPlugin->packageOptions();
	$url = 'http://' . Duoshuo_Abstract::DOMAIN . '/connect-site/?'. http_build_query($params, null, '&');
	header("Location:" . $url, true);
	exit;
}

$duoshuoAdmin = new Duoshuo_Admin();

//版本检查，5.7及之后使用css/base.css
$versionArray = array();
preg_match('/V(\d+)_?/',$duoshuoAdmin->getGlobal('version'),$versionArray);
if(isset($versionArray[1]) && $versionArray[1] > 56)
{
	define('CSSPATH', 'css');
	define('IMAGEPATH', 'images');
}else
{
	define('CSSPATH', 'img');
	define('IMAGEPATH', 'img');
}

//执行操作
if(isset($action)){
	if (method_exists( $duoshuoAdmin, $action ) === TRUE){
		$duoshuoAdmin->$action();
	}
	else
		Showmsg('没有此操作',1,2);
}
