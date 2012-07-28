<?php 

class Duoshuo_Admin{
	function manageComments(){
		$adminPath = 'admin/';
		require DEDEROOT.'/plus/duoshuo/templets/admin.htm';
	}
	
	function adminUsers(){
		$adminPath = 'admin/users/';
		require DEDEROOT.'/plus/duoshuo/templets/admin.htm';
	}
	function adminSettings(){
		$adminPath = 'admin/settings/';
		require DEDEROOT.'/plus/duoshuo/templets/admin.htm';
	}
	function localConfig(){
		require DEDEROOT.'/plus/duoshuo/templets/local_config.htm';
	}
	
	function saveLocalConfig(){
		$keys = array('short_name','secret','seo_enabled');
		$duoshuoPlugin = Duoshuo_Dedecms::getInstance();
		
		foreach ($keys as $key){
			if(isset($_POST[$key])){
				 $duoshuoPlugin->updateOption($key,$_POST[$key]);
			}
		}
		
		Showmsg('保存成功！','./duoshuo.php?action=localConfig',0,1000);
	}
	
	
	function helpDocument(){
		require DEDEROOT.'/plus/duoshuo/templets/help_document.htm';
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
			if(isset($_POST['next_step'])){
				Showmsg('备份成功！','./duoshuo.php?action='.$_POST['next_step'],0,1000);
			}else{
				Showmsg('备份成功！','./duoshuo.php?action=localConfig',0,1000);
			}
		}else{
			Showmsg(DEDETEMPLATE.'/default/ajaxfeedback.bak.htm'.'不存在，请手动插入标签','./duoshuo.php?action=localConfig',0,2000);
		}
	}
	
	function installStep1(){
		require DEDEROOT.'/plus/duoshuo/templets/replace_tag.htm';
	}
	
}
