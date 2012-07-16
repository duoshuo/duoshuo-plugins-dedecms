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
require_once(DEDEROOT.'/plus/duoshuo/duoshuo.php');

require_once(DEDEROOT.'/plus/duoshuo/duoshuo_admin.php');

// 设置默认参数
Duoshuo::checkDefaultSettings(dirname(__FILE__));

//从服务器返回的注册结果
if(!empty($_GET) && isset($_GET['short_name']) && isset($_GET['secret'])){
	Duoshuo::saveConfig('short_name',$_GET['short_name']);
	Duoshuo::saveConfig('secret',$_GET['secret']);	
}
Duoshuo::init();
$duoshuoAdmin = new DuoshuoAdmin();

//兼容0.1.x版本插件 引入多说配置文件 {{ 只用于 0.2.x
$configFile =  DEDEDATA.'/duoshuo.inc.php';

if(file_exists($configFile)){
	if(empty(Duoshuo::$shortName) || empty(Duoshuo::$secret)){//如果数据库设置不完整,从文件导入
		include_once $configFile;
		global $cfg_duoshuo;
		if(isset($cfg_duoshuo))
		{
			if(empty(Duoshuo::$shortName) && !empty($cfg_duoshuo['short_name'])){
				Duoshuo::saveConfig('short_name',$cfg_duoshuo['short_name']);
				Duoshuo::$shortName = $cfg_duoshuo['short_name'];
			}
			if(empty(Duoshuo::$secret) && !empty($cfg_duoshuo['secret'])){
				Duoshuo::saveConfig('secret',$cfg_duoshuo['secret']);
				Duoshuo::$secret = $cfg_duoshuo['secret'];
			}
		}
	}

	if(!empty(Duoshuo::$shortName) && !empty(Duoshuo::$secret)){
		$success = unlink($configFile);//如果数据库已经设置，删除文件
	}
}
//}}

if(empty(Duoshuo::$shortName) || empty(Duoshuo::$secret)){
	$params = Duoshuo::packageOptions();
	$url = 'http://' . Duoshuo::DOMAIN . '/connect-site/?'. http_build_query($params, null, '&');
	header("Location:" . $url, true);
	exit;
}


if(Duoshuo::$adminPath !== dirname(__FILE__) && Duoshuo::$syncToLocal && Duoshuo::$seoEnabled){
	//管理路径不对，发出提醒
}

if(isset($action)){
	if (method_exists( $duoshuoAdmin, $action ) === TRUE){
		$duoshuoAdmin->$action();
	}
	else
		Showmsg('没有此操作',1,2);
}