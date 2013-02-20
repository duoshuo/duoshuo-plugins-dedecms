<?php if(!defined('DEDEINC')) exit('Request Error!');
require_once(DEDEROOT.'/plus/duoshuo.php');
require_once(DEDEROOT.'/include/taglib/feedback.lib.php');

function lib_duoshuo(&$ctag,&$refObj)
{
	global $dsql,$cfg_basehost,$cfg_cmspath;
	
	$plugin = Duoshuo_Dedecms::getInstance();
	
	if ($plugin->getOption('short_name') == '' || $plugin->getOption('secret') == '') 
		return '在管理后台进行一步配置，就可以开始使用多说了';
	
	$attlist='type|0';
	FillAttsDefault($ctag->CAttribute->Items,$attlist);
	extract($ctag->CAttribute->Items, EXTR_SKIP);
	
	if(empty($refObj->Fields['aid'])){
		return '';
	}
	$arcid = $refObj->Fields['aid'];
	
	//设置参数
	$attrs = array();
	$attrs[] = ' data-thread-key="'.$arcid.'"';
	$attrs[] = 'data-author-key="'.$refObj->Fields["mid"].'"';
	if(empty($refObj->Fields['arcurl'])){
		$refObj->Fields['arcurl'] = $refObj->GetTrueUrl(null);
	}
	if(strpos($refObj->Fields['arcurl'],$cfg_basehost) === false){
		$attrs[] = ' data-url="'.$cfg_basehost.$refObj->Fields['arcurl'].'"';
	}
	else{
		$attrs[] = ' data-url="'.$refObj->Fields['arcurl'].'"';
	}
	if(!empty($refObj->Fields['litpic']) && !preg_match('/\/images\/defaultpic.gif/',$refObj->Fields['litpic'])){
		if(preg_match('/http:\/\//',$refObj->Fields['litpic'])){
			$attrs[] = ' data-image="'.$refObj->Fields['litpic'].'"';
		}else{
			$attrs[] = ' data-image="'.$cfg_basehost.$refObj->Fields['litpic'].'"';
		}
	}
	
	if(!empty($refObj->Fields['title'])){
		$attrs[] = 'data-title="'.htmlspecialchars($refObj->Fields['title'],ENT_QUOTES,'GB2312').'"';
	}
	
	//输出评论框
	ob_start();
	require (DEDEROOT.'/plus/duoshuo/templates/comments.htm');
	
	if($plugin->getOption('seo_enabled') && !empty($arcid)){
		// 每篇评论最大字数
		$infolen = 200;
		// 每篇文章seo显示的最大行数
		$totalrow = 100;
		
		$innertext  = file_get_contents(DEDEROOT.'/plus/duoshuo/templates/comments_seo.htm');

?>
<div id="ds-ssr" class="mt1">
	<dl class="tbox">
		<dt> <strong>评论列表（网友评论仅供网友表达个人看法，并不表明本站同意其观点或证实其描述）</strong> </dt>
		<dd>
			<div class="dede_comment">
				<div class="decmt-box1">
					<ul>
						<li id="commetcontentNew"></li>

<?php 		
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
			echo $ctp->GetResult();
		}
?> 
					</ul>
				</div>
			</div>
		</dd>
	</dl>
</div>

<?php 
	}

	return ob_get_clean();
}