<?php   if(!defined('DEDEINC')) exit('Request Error!');

 
function lib_duoshuo(&$ctag,&$refObj)
{
    global $dsql,$envs,$cfg_phpurl,$cfg_basehost,$cfg_multi_site;

    $attlist='type|0';
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    include(DEDEDATA.'/duoshuo.inc.php');//定义了$cfg_duoshuo
    
    if (!isset($cfg_duoshuo['short_name']) || !isset($cfg_duoshuo['secret']))  return '在管理后台进行一步配置，就可以开始使用多说了';
    $short_name = $cfg_duoshuo['short_name'];
    
    $arcid = !empty($refObj->Fields['aid']) ? $refObj->Fields['aid'] : 0;
    $arctitle = !empty($refObj->Fields['title']) ? $refObj->Fields['title'] : 0;
    
    $data_local_thread_id = !empty($arcid) ? ' data-local-thread-id="'.$arcid.'"' : '';
    $data_title = !empty($arctitle) ? ' data-title="'.$arctitle.'"' : '';
    
    
    $reval = <<<EOT
<!-- Duoshuo Comment BEGIN -->
	<div class="ds-thread"$data_local_thread_id$data_title></div>
	<script type="text/javascript">
	var duoshuoQuery = {short_name: "$short_name"};
	(function() {
		var ds = document.createElement('script');
		ds.type = 'text/javascript';ds.async = true;
		ds.src = 'http://static.duoshuo.com/embed.js';
		ds.charset = 'UTF-8';
		(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ds);
	})();
	</script>
<!-- Duoshuo Comment END -->
EOT;
    return $reval;
}