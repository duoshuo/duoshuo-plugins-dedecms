<?php 

class Duoshuo_Admin{
	function manageComments(){
		$ajax_comment_file = DEDETEMPLATE.'/default/ajaxfeedback.htm';
	
		$tag_replaced =  false;
	
		if(file_exists($ajax_comment_file)){
			$comment_content = file_get_contents($ajax_comment_file);
			
			if(strpos($comment_content, Duoshuo_Dedecms::$commentTag)!==false){
				$tag_replaced = true;
			}
		}
	
		$params = array(
				'template'		=>	'dedecms',
				//'remote_auth'	=>	Duoshuo_Abstract::remoteAuth(),
		);
	
		require DEDEROOT.'/plus/duoshuo/templets/duoshuo_manage.htm';
	}
	
	function syncComments(){
		require DEDEROOT.'/plus/duoshuo/templets/duoshuo_sync.htm';
	}
	
	function userProfile(){
		//TODO
	}
	
	function replaceCommentTag(){
		$ajax_comment_file = DEDETEMPLATE.'/default/ajaxfeedback.htm';
		$ajax_comment_file_bak = DEDETEMPLATE.'/default/ajaxfeedback.bak.htm';
		if(file_exists($ajax_comment_file)){
			if(!file_exists($ajax_comment_file_bak)){
				$ret = copy($ajax_comment_file, $ajax_comment_file_bak);
				if(!$ret){
					Showmsg('备份'.$ajax_comment_file.'失败，请检查该目录权限，或手动复制到'.$ajax_comment_file_bak,-1,0,2000);
				}
			}
			file_put_contents($ajax_comment_file, Duoshuo_Dedecms::$commentTag);
			Showmsg('备份成功！',-1,0,2000);
		}else{
			Showmsg(DEDETEMPLATE.'/default/ajaxfeedback.bak.htm'.'不存在，请自行插入标签',-1,0,2000);
		}
	}
}
