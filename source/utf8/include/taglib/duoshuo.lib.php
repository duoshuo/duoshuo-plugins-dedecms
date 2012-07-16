<?php if(!defined('DEDEINC')) exit('Request Error!');
require_once(DEDEROOT.'/plus/duoshuo/duoshuo.php');
require_once(DEDEROOT.'/include/taglib/feedback.lib.php');
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
	require (DEDEROOT.'/plus/duoshuo/templets/comments.htm');
	$reval = ob_get_clean();
	
	if(Duoshuo::$seoEnabled && !empty($arcid)){
		$infolen = 200;//每篇评论最大字数
		$totalrow = Duoshuo::$seoMaxRow;
		ob_start();
		include (DEDEROOT.'/plus/duoshuo/templets/comments_seo.htm');
		$innertext = ob_get_clean();		
		
		$seoComments = 
'<div id="ds-ssr" class="mt1">
	<dl class="tbox">
		<dt> <strong>评论列表（网友评论仅供网友表达个人看法，并不表明本站同意其观点或证实其描述）</strong> </dt>
		<dd>
			<div class="dede_comment">
				<div class="decmt-box1">
					<ul>
						<li id="commetcontentNew"></li>
';
		
		$wsql = " WHERE ischeck=1 AND aid = $arcid";
		$equery = "SELECT * FROM `#@__feedback` $wsql ORDER BY id DESC LIMIT 0 , $totalrow";
		$ctp = new DedeTagParse();
		$ctp->SetNameSpace('field','[',']');
		$ctp->LoadSource($innertext);
		
		$dsql->Execute('fb',$equery);
		while($arr=$dsql->GetArray('fb'))
		{
			$arr['msg'] = jsTrim(Html2Text($arr['msg']),$infolen);
			$arr['dtime'] = GetDateTimeMK($arr['dtime']);
			$arr['username'] = $str = str_replace('&lt;br/&gt;',' ',$arr['username']);
			foreach($ctp->CTags as $tagid=>$ctag)
			{
				if(!empty($arr[$ctag->GetName()]))
				{
					$ctp->Assign($tagid,$arr[$ctag->GetName()]);
				}
			}
			$seoComments .= $ctp->GetResult();
		}
		$seoComments .= 
'					</ul>
				</div>
			</div>
		</dd>
	</dl>
</div>'."\n";
		$reval .= $seoComments;
	}
	
	return $reval;
}