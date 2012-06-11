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
require_once('./duoshuo.class.php');

$duoshuo = new Duoshuo;

if(isset($action)){
	if (method_exists ( $duoshuo, $action ) === TRUE){
		$duoshuo->$action();
	}
	else
		Showmsg('没有此操作',1,2);
}