<?php
/**
 * ��˵���
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

$duoshuoPlugin = Duoshuo_Dedecms::getInstance();
// ����Ĭ�ϲ���
$duoshuoPlugin->checkDefaultSettings();


//�ӷ��������ص�ע����
if(!empty($_GET) && isset($_GET['short_name']) && isset($_GET['secret'])){
	$keys = array('short_name','secret');
	foreach ($keys as $key){
		$duoshuoPlugin->updateOption($key,$_GET[$key]);
	}
}

//����0.1.x�汾��� �����˵�����ļ� {{ ֻ���� 0.2.x
$configFile =  DEDEDATA.'/duoshuo.inc.php';

if(file_exists($configFile)){
	if($duoshuoPlugin->getOption('short_name') == '' ||$duoshuoPlugin->getOption('secret') == ''){//������ݿ����ò�����,���ļ�����
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
		$success = unlink($configFile);//������ݿ��Ѿ����ã�ɾ���ļ�
	}
}
//}}

if($duoshuoPlugin->getOption('short_name') == '' || $duoshuoPlugin->getOption('secret') == ''){
	$params = $duoshuoPlugin->packageOptions();
	$url = 'http://' . Duoshuo_Abstract::DOMAIN . '/connect-site/?'. http_build_query($params, null, '&');
	header("Location:" . $url, true);
	exit;
}

$duoshuoAdmin = new Duoshuo_Admin();

if(isset($action)){
	if (method_exists( $duoshuoAdmin, $action ) === TRUE){
		$duoshuoAdmin->$action();
	}
	else
		Showmsg('û�д˲���',1,2);
}