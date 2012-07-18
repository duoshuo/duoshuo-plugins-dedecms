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

require_once(DEDEROOT.'/plus/duoshuo.php');
require_once(DEDEROOT.'/plus/duoshuo/Admin.php');

$duoshuoPlugin = new Duoshuo_Dedecms();
// 设置默认参数
$duoshuoPlugin->checkDefaultSettings();


//从服务器返回的注册结果
if(!empty($_GET) && isset($_GET['short_name']) && isset($_GET['secret'])){
	$duoshuoPlugin->updateOption('short_name',$_GET['short_name']);
	$duoshuoPlugin->updateOption('secret',$_GET['secret']);	
}

//兼容0.1.x版本插件 引入多说配置文件 {{ 只用于 0.2.x
$configFile =  DEDEDATA.'/duoshuo.inc.php';

if(file_exists($configFile)){
	if(empty($duoshuoPlugin->shortName) || empty($duoshuoPlugin->secret)){//如果数据库设置不完整,从文件导入
		include_once $configFile;
		global $cfg_duoshuo;
		if(isset($cfg_duoshuo))
		{
			if(empty($duoshuoPlugin->shortName) && !empty($cfg_duoshuo['short_name'])){
				$duoshuoPlugin->updateOption('short_name', $cfg_duoshuo['short_name']);
				$duoshuoPlugin->shortName = $cfg_duoshuo['short_name'];
			}
			if(empty($duoshuoPlugin->secret) && !empty($cfg_duoshuo['secret'])){
				$duoshuoPlugin->updateOption('secret',$cfg_duoshuo['secret']);
				$duoshuoPlugin->secret = $cfg_duoshuo['secret'];
			}
		}
	}

	if(!empty($duoshuoPlugin->shortName) && !empty($duoshuoPlugin->secret)){
		$success = unlink($configFile);//如果数据库已经设置，删除文件
	}
}
//}}

if(empty($duoshuoPlugin->shortName) || empty($duoshuoPlugin->secret)){
	$params = $duoshuoPlugin->packageOptions();
	$url = 'http://' . Duoshuo::DOMAIN . '/connect-site/?'. http_build_query($params, null, '&');
	header("Location:" . $url, true);
	exit;
}

$duoshuoAdmin = new Duoshuo_Admin();

if(isset($action)){
	if (method_exists( $duoshuoAdmin, $action ) === TRUE){
		$duoshuoAdmin->$action();
	}
	else
		Showmsg('没有此操作',1,2);
}
