<?php if(!defined('DEDEINC')) exit('Request Error!');
require_once(DEDEROOT.'/plus/duoshuo/duoshuo.php');
function lib_duoshuo(&$ctag,&$refObj)
{
	if(!Duoshuo::$initialized){
		
		Duoshuo::init();
	}
	global $dsql,$envs,$cfg_phpurl,$cfg_basehost,$cfg_multi_site;
	$attlist='type|0';
	FillAttsDefault($ctag->CAttribute->Items,$attlist);
	extract($ctag->CAttribute->Items, EXTR_SKIP);
	
	if (empty(Duoshuo::$shortName) || empty(Duoshuo::$secret))  return '在管理后台进行一步配置，就可以开始使用多说了';
	$short_name = Duoshuo::$shortName;
	
	$arcid = !empty($refObj->Fields['aid']) ? $refObj->Fields['aid'] : 0;
	$arctitle = !empty($refObj->Fields['title']) ? $refObj->Fields['title'] : 0;
	
	$data_source_thread_id = !empty($arcid) ? ' data-source-thread-id="'.$arcid.'"' : '';
	$data_title = !empty($arctitle) ? ' data-title="'.htmlspecialchars($arctitle).'"' : '';
	
	ob_start();
	require (DEDEROOT.'/plus/duoshuo/templets/duoshuo_comments.htm');
	$reval = ob_get_clean();
	
	return $reval;
}